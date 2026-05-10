<?php

namespace Database\Seeders;

use App\Models\Moving\MovingOrder;
use App\Models\Moving\MovingOrderPhoto;
use Illuminate\Database\Seeder;

class MovingOrderPhotosSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creating moving order photos...');

        $orders = MovingOrder::whereIn('metadata->slug', [
            'narvik-fagerneset',
            'ankenes-narvik',
            'bjerkvik-narvik-office',
        ])->get();

        foreach ($orders as $order) {
            // Pre-move photos
            MovingOrderPhoto::updateOrCreate(
                [
                    'moving_order_id' => $order->id,
                    'collection_name' => 'pre_move_photos',
                    'file_path' => "photos/moving/{$order->id}/before1.jpg",
                ],
                [
                    'file_name' => 'before1.jpg',
                    'mime_type' => 'image/jpeg',
                    'file_size' => 245000,
                    'description' => 'Фото квартиры/дома до переезда',
                    'metadata' => [
                        'type' => 'before',
                        'room' => 'living_room',
                    ],
                ]
            );

            // Post-move photos
            MovingOrderPhoto::updateOrCreate(
                [
                    'moving_order_id' => $order->id,
                    'collection_name' => 'post_move_photos',
                    'file_path' => "photos/moving/{$order->id}/after1.jpg",
                ],
                [
                    'file_name' => 'after1.jpg',
                    'mime_type' => 'image/jpeg',
                    'file_size' => 238000,
                    'description' => 'Фото квартиры/дома после переезда',
                    'metadata' => [
                        'type' => 'after',
                        'room' => 'living_room',
                    ],
                ]
            );
        }

        $this->command->info('Moving order photos created.');
    }
}
