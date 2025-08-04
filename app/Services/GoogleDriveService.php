<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission; 
use App\Models\PhotobookOrder; 
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // Untuk sanitasi nama folder
use Illuminate\Support\Facades\Storage; // Untuk membaca file kredensial jika disimpan di storage

class GoogleDriveService
{
    protected $client;
    protected $service;
    protected $rootFolderId; 
    protected $parentFolderId;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName('Photobook App');
        $this->client->setScopes([Drive::DRIVE_FILE]); 
        // --- Autentikasi dengan Service Account ---
        $credentialsPath = env('GOOGLE_DRIVE_SERVICE_ACCOUNT_CREDENTIALS_PATH');
        // $credentialsJson = env('GOOGLE_DRIVE_SERVICE_ACCOUNT_CREDENTIALS_JSON'); // Jika menggunakan JSON langsung

        if ($credentialsPath && file_exists($credentialsPath)) {
            $this->client->setAuthConfig($credentialsPath);
        }
        // elseif ($credentialsJson) {
        //     $this->client->setAuthConfig(json_decode($credentialsJson, true));
        // }
        else {
            throw new \Exception("Google Drive Service Account credentials not found. Check your .env file.");
        }

        $this->service = new Drive($this->client);
        // --- Akhir Autentikasi ---

        // Jika Anda memiliki folder root khusus di Drive, simpan ID-nya
        // $this->rootFolderId = env('GOOGLE_DRIVE_ROOT_FOLDER_ID', 'root'); // 'root' berarti My Drive utama
        // $this->rootFolderId = 'root'; // Untuk sekarang, gunakan My Drive utama
        $this->parentFolderId = env('GOOGLE_DRIVE_PARENT_FOLDER_ID', 'root');
    }

    /**
     * Membuat folder Google Drive untuk sebuah order.
     *
     * @param PhotobookOrder $order
     * @return string URL folder yang dibuat
     * @throws \Exception Jika pembuatan folder gagal
     */
    public function createOrderFolder(PhotobookOrder $order): string
    {
        try {
            // --- 1. Siapkan nama folder utama ---
            $orderNumber = $order->order_number;
            // Sanitasi nama customer untuk nama folder
            $sanitizedCustomerName = Str::slug($order->customer_name, '_');
            $mainFolderName = "{$orderNumber}_{$sanitizedCustomerName}";
            // --- Akhir 1 ---

            // --- 2. Buat folder utama di dalam parent folder yang ditentukan ---
            $mainFolderMetadata = new DriveFile([
                'name' => $mainFolderName,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$this->parentFolderId] // Gunakan parent folder ID dari .env
            ]);

            $createdMainFolder = $this->service->files->create($mainFolderMetadata, [
                'fields' => 'id,webViewLink'
            ]);

            $mainFolderId = $createdMainFolder->id;
            $mainFolderUrl = $createdMainFolder->webViewLink;

            if (!$mainFolderId || !$mainFolderUrl) {
                 throw new \Exception("Failed to retrieve main folder ID or URL after creation.");
            }

            Log::info("Main Google Drive folder created successfully for Order ID: {$order->id}", [
                'main_folder_id' => $mainFolderId,
                'main_folder_url' => $mainFolderUrl
            ]);
            // --- Akhir 2 ---

            // --- 3. Buat sub-folder untuk setiap item order ---
            foreach ($order->items as $item) {
                // --- Siapkan nama sub-folder ---
                $itemId = $item->id;
                $productName = $item->product ? $item->product->name : 'No_Product';
                $templateName = $item->template ? $item->template->name : 'No_Template';
                
                // Sanitasi nama untuk nama folder
                $sanitizedProductName = Str::slug($productName, '_');
                $sanitizedTemplateName = Str::slug($templateName, '_');
                
                $subFolderName = "{$itemId}_{$sanitizedProductName}_{$sanitizedTemplateName}";
                // --- Akhir Siapkan nama ---
                
                // --- Buat sub-folder ---
                $subFolderMetadata = new DriveFile([
                    'name' => $subFolderName,
                    'mimeType' => 'application/vnd.google-apps.folder',
                    'parents' => [$mainFolderId] // Parent adalah folder utama yang baru dibuat
                ]);

                $createdSubFolder = $this->service->files->create($subFolderMetadata, [
                    'fields' => 'id,webViewLink'
                ]);
                
                $subFolderId = $createdSubFolder->id;
                // Opsional: Simpan URL sub-folder jika perlu diakses secara terpisih
                // $subFolderUrl = $createdSubFolder->webViewLink; 
                
                Log::info("Sub-folder created for Order Item ID: {$itemId} inside main folder ID: {$mainFolderId}", [
                    'sub_folder_id' => $subFolderId,
                    // 'sub_folder_url' => $subFolderUrl
                ]);
                // --- Akhir Buat sub-folder ---
                
                // --- (Opsional) Berikan permission untuk sub-folder ---
                // Jika diperlukan, Anda bisa memberikan permission khusus untuk setiap sub-folder di sini
                // ... 
                // --- Akhir Permission sub-folder ---
            }
            // --- Akhir 3 ---

            // --- 4. Berikan Akses (Sama seperti sebelumnya, opsional) ---
            // Berikan akses Owner/Writer kepada admin
            $adminEmail = env('GOOGLE_DRIVE_ADMIN_EMAIL');
            if ($adminEmail) {
                try {
                    // Memberikan akses ke folder utama. Sub-folder biasanya mewarisi permission.
                    $adminPermission = new Permission([
                        'type' => 'user',
                        'role' => 'writer', // Atau 'owner'
                        'emailAddress' => $adminEmail,
                    ]);
                    $this->service->permissions->create($mainFolderId, $adminPermission, [
                        'fields' => 'id'
                    ]);
                    Log::info("Access granted to admin {$adminEmail} for main folder ID {$mainFolderId}");
                } catch (\Exception $e) {
                    Log::warning("Failed to grant access to admin {$adminEmail} for main folder ID {$mainFolderId}. Error: " . $e->getMessage());
                }
            }
            
            // Berikan akses Writer kepada customer atau anyone
            // Karena ada beberapa sub-folder, memberikan akses ke folder utama biasanya cukup
            // karena permission sering kali diwariskan. 
            // Namun, jika diperlukan kontrol lebih spesifik per item, logikanya bisa ditambahkan di sini.
            // ... (logika pemberian akses customer/anyone seperti sebelumnya, diterapkan ke $mainFolderId) ...
            // --- Akhir 4 ---

            // Kembalikan URL folder utama.
            return $mainFolderUrl; 

        } catch (\Exception $e) {
            Log::error("Failed to create hierarchical Google Drive folder structure for Order ID: {$order->id}.", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Pertimbangkan untuk menghapus folder utama yang mungkin sudah dibuat jika proses gagal di tengah jalan
            // if (isset($mainFolderId)) {
            //     try {
            //         $this->service->files->delete($mainFolderId);
            //         Log::info("Rolled back main folder ID {$mainFolderId} due to failure.");
            //     } catch (\Exception $deleteEx) {
            //         Log::error("Failed to rollback main folder ID {$mainFolderId} after failure. Manual cleanup might be needed. Error: " . $deleteEx->getMessage());
            //     }
            // }
            throw new \Exception("Google Drive hierarchical folder creation failed: " . $e->getMessage(), 0, $e);
        }
    }

    // Method lain untuk mengelola file dalam folder bisa ditambahkan di sini jika diperlukan di masa depan
}
