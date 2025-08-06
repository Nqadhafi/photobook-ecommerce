<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\PhotobookTemplate; // Sesuaikan dengan nama model template Anda
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage; // Untuk mengelola file upload
use Illuminate\Validation\Rule;

class AdminTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
        ]);

        $query = PhotobookTemplate::with('product'); // ⚡ load relasi product

        if (!empty($validated['search'])) {
            $query->where('name', 'like', '%' . $validated['search'] . '%');
        }

        $perPage = $validated['per_page'] ?? 15;
        $templates = $query->latest()->paginate($perPage);

        return response()->json($templates);
    }


    /**
     * Store a newly created resource in storage.
     * Digunakan oleh Super Admin untuk membuat template baru.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:photobook_templates,name', // Sesuaikan nama tabel
            'product_id' => [
                'required',
                'integer',
                Rule::exists('photobook_products', 'id'), // Pastikan produk ada
            ],
            'sample_image' => 'nullable|image|max:2048', // Maksimal 2MB, sesuaikan jika perlu
            // Validasi untuk layout_data sebagai JSON string atau array
            // Jika sebagai string JSON:
            // 'layout_data' => 'required|json',
            // Jika ingin memvalidasi struktur JSON, gunakan aturan kustom atau validasi di backend setelah decoding
            'layout_data' => 'required|array', // Misalnya, menerima array associative
            'layout_data.pages' => 'required|integer|min:1',
            'layout_data.dimensions' => 'required|string|max:50',
            'layout_data.layout_type' => 'required|string|max:100',
            'layout_data.photo_slots' => 'required|integer|min:0',
            
            // Tambahkan validasi untuk field lain dalam layout_data jika diperlukan
            // Tambahkan validasi untuk field lain jika ada
        ]);

        try {
            // Jika layout_data dikirim sebagai array, dan perlu disimpan sebagai JSON di database
            // Model Eloquent biasanya secara otomatis meng-handle ini jika $casts sudah diatur
            // di model. Tapi untuk keamanan, kita bisa encode:
            // $validated['layout_data'] = json_encode($validated['layout_data']);
            //upload sample_image jika ada
            if ($request->hasFile('sample_image')) {
                $validated['sample_image'] = $request->file('sample_image')->store('templates', 'public'); // Simpan di storage/app/public/templates
            }
            $template = PhotobookTemplate::create($validated);

            return response()->json($template, 201);
        } catch (\Exception $e) {
            Log::error('Failed to create template: ' . $e->getMessage(), ['validated_data' => array_merge($validated, ['layout_data' => json_encode($validated['layout_data'] ?? null)])]);
            return response()->json(['error' => 'Failed to create template. Please try again.'], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PhotobookTemplate  $template
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(PhotobookTemplate $template): JsonResponse
    {
        return response()->json($template);
    }

    /**
     * Update the specified resource in storage.
     * Digunakan oleh Super Admin untuk mengubah template.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PhotobookTemplate  $template
     * @return \Illuminate\Http\JsonResponse
     */
public function update(Request $request, PhotobookTemplate $template): JsonResponse
{
    $validated = $request->validate([
        'name' => 'sometimes|required|string|max:255|unique:photobook_templates,name,' . $template->id,
        'sample_image' => 'nullable|image|max:2048',
        'layout_data' => 'sometimes|required|array',
        'layout_data.pages' => 'sometimes|required|integer|min:1',
        'layout_data.dimensions' => 'sometimes|required|string|max:50',
        'layout_data.layout_type' => 'sometimes|required|string|max:100',
        'layout_data.photo_slots' => 'sometimes|required|integer|min:0',
    ]);

    try {
        // Upload sample_image jika ada
        if ($request->hasFile('sample_image')) {
            // Hapus file lama jika ada
            if ($template->sample_image && Storage::disk('public')->exists($template->sample_image)) {
                Storage::disk('public')->delete($template->sample_image);
            }

            // Simpan file baru
            $validated['sample_image'] = $request->file('sample_image')->store('templates', 'public'); 
            // hasil: templates/nama_file.jpg
        }

        // Update template
        $template->update($validated);

        return response()->json($template);
    } catch (\Exception $e) {
        Log::error('Failed to update template ID ' . $template->id . ': ' . $e->getMessage(), [
            'validated_data' => array_merge($validated, ['layout_data' => json_encode($validated['layout_data'] ?? null)])
        ]);

        return response()->json(['error' => 'Failed to update template. Please try again.'], 500);
    }
}


    /**
     * Remove the specified resource from storage.
     * Digunakan oleh Super Admin untuk menghapus template.
     * Hati-hati: Menghapus template yang digunakan oleh produk/order bisa menyebabkan masalah.
     *
     * @param  \App\Models\PhotobookTemplate  $template
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(PhotobookTemplate $template): JsonResponse
    {
        try {
            // Mencegah penghapusan jika template sedang digunakan
            // Misalnya, cek relasi dengan PhotobookOrderItem atau PhotobookProduct
            // Anda perlu menyesuaikan nama relasi berdasarkan model Anda.
            // Contoh jika ada relasi 'items' di model PhotobookTemplate:
            $isInUse = $template->items()->exists();
            if ($isInUse) {
                return response()->json(['error' => 'Cannot delete template because it is associated with orders or products.'], 400);
            }

            $template->delete();

            return response()->json(['message' => 'Template deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Failed to delete template ID ' . $template->id . ': ' . $e->getMessage());
            
            // Tangani error khusus jika ada constraint
            if ($e->getCode() == 23000) {
                 return response()->json(['error' => 'Cannot delete template because it is associated with data.'], 400);
            }
            
            return response()->json(['error' => 'Failed to delete template. Please try again.'], 500);
        }
    }
}
