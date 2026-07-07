<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Model>
 */
class LibraryAccessLogFactory extends Factory
{
    protected $model = LibraryAccessLogFactoryModel::class;

    public function definition(): array
    {
        return [
            'library_item_id' => null,
            'user_id' => null,
            'action' => fake()->randomElement(['view', 'download']),
            'ip_address' => fake()->ipv4(),
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}

class LibraryAccessLogFactoryModel extends Model
{
    public $timestamps = false;

    protected $table = 'library_access_logs';

    protected $guarded = [];
}
