<?php 

namespace Database\Factories;

use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition()
    {
        $users = User::factory(2)->create();
        return [
            'name' => $this->faker->word,
            'user_id' => function () use ($users) {
                // Elige aleatoriamente entre los dos usuarios creados anteriormente.
                return $this->faker->randomElement($users)->id;
            },
            'price_id' => function () {
                // Puedes ajustar esto según tus necesidades. Aquí estoy asumiendo que tienes un modelo Price.
                return \App\Models\Price::factory()->create()->id;
            },
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}