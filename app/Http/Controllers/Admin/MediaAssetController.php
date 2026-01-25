<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaAssetStoreRequest;
use App\Models\MediaAsset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class MediaAssetController extends Controller
{
    public function index(): JsonResponse
    {
        $assets = MediaAsset::query()
            ->latest()
            ->get(['id', 'tipo', 'path', 'alt_text']);

        return response()->json($assets);
    }

    public function store(MediaAssetStoreRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $path = $file->store('media', 'public');

        $asset = MediaAsset::create([
            'tipo' => 'image',
            'path' => $path,
            'alt_text' => $request->input('alt_text'),
        ]);

        return response()->json([
            'id' => $asset->id,
            'path' => Storage::disk('public')->url($asset->path),
            'alt_text' => $asset->alt_text,
        ]);
    }
}
