<?php 

namespace Database\Factories;

use App\Models\Price;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PriceFactory extends Factory
{
    protected $model = Price::class;

    public function definition()
    {
        $users = User::factory(2)->create();
        return [
            'price' => $this->faker->randomFloat(2, 10, 100),
            'user_id' => function () use ($users) {
                // Elige aleatoriamente entre los dos usuarios creados anteriormente.
                return $this->faker->randomElement($users)->id;
            },
            'description' => $this->faker->text,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}