<?php

namespace App\Http\Controllers;
use App\Models\PhotobookTemplate;
use Illuminate\Http\JsonResponse;

use Illuminate\Http\Request;

class TemplateController extends Controller
{
    //
    public function show(PhotobookTemplate $template): JsonResponse
    {
        if (!$template->is_active) {
            return response()->json(['error' => 'Template not found'], 404);
        }

        // Load template with product
        $template->load('product');

        return response()->json([
            'data' => $template
        ]);
    }
}
