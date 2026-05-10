<?php

namespace App\Http\Controllers\Api\Moving;

use App\Http\Controllers\Controller;
use App\Models\Moving\MovingOrder;
use App\Models\Moving\MovingOrderPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class PhotoController extends Controller
{
    public function index(MovingOrder $movingOrder)
    {
        $photos = $movingOrder->photos()->latest()->get()->map(fn (MovingOrderPhoto $photo) => [
            'id' => $photo->id,
            'file_name' => $photo->file_name,
            'url' => Storage::disk('public')->url($photo->file_path),
            'collection' => $photo->collection_name,
            'latitude' => $photo->latitude,
            'longitude' => $photo->longitude,
            'description' => $photo->description,
            'created_at' => $photo->created_at,
        ]);

        return response()->json([
            'data' => $photos,
        ]);
    }

    public function store(Request $request, MovingOrder $movingOrder)
    {
        $data = $request->validate([
            'collection_name' => 'nullable|string|in:pre_move_photos,post_move_photos,damage_photos',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'description' => 'nullable|string|max:500',
            'photos' => 'required|array|min:1|max:10',
            'photos.*' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $stored = collect($request->file('photos'))
            ->filter(fn (?UploadedFile $file) => $file !== null)
            ->map(function (UploadedFile $file) use ($movingOrder, $data) {
                $directory = 'moving-orders/'.$movingOrder->id.'/photos';
                $path = $file->store($directory, 'public');

                return $movingOrder->photos()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'collection_name' => $data['collection_name'] ?? 'pre_move_photos',
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'description' => $data['description'] ?? null,
                ]);
            })
            ->map(fn (MovingOrderPhoto $photo) => [
                'id' => $photo->id,
                'file_name' => $photo->file_name,
                'url' => Storage::disk('public')->url($photo->file_path),
                'collection' => $photo->collection_name,
                'latitude' => $photo->latitude,
                'longitude' => $photo->longitude,
            ]);

        return response()->json([
            'message' => 'Фото збережено',
            'data' => $stored,
        ], Response::HTTP_CREATED);
    }

    public function destroy(MovingOrder $movingOrder, MovingOrderPhoto $photo)
    {
        abort_if($photo->moving_order_id !== $movingOrder->id, Response::HTTP_FORBIDDEN);

        Storage::disk('public')->delete($photo->file_path);
        $photo->delete();

        return response()->json([
            'message' => 'Фото видалено',
        ]);
    }
}
