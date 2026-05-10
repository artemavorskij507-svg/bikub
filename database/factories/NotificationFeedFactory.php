<?php

namespace Database\Factories;

use App\Models\NotificationFeed;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationFeed>
 */
class NotificationFeedFactory extends Factory
{
    protected $model = NotificationFeed::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'order.created',
            'category' => 'order',
            'title' => 'Тестовое уведомление',
            'body' => $this->faker->sentence(),
            'data' => ['foo' => 'bar'],
        ];
    }
}
