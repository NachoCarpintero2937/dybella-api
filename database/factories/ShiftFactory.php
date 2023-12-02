<?php

namespace Database\Factories;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;
    public function definition()
    {
        $users = User::factory(2)->create();
        return [
            'service_id' => function () {
                // Puedes ajustar esto según tus necesidades. Aquí estoy asumiendo que tienes un modelo Service.
                return \App\Models\Service::factory()->create()->id;
            },
            'client_id' => function () {
                // Puedes ajustar esto según tus necesidades. Aquí estoy asumiendo que tienes un modelo Client.
                return \App\Models\Client::factory()->create()->id;
            },
            'user_id' => function () use ($users) {
                // Elige aleatoriamente entre los dos usuarios creados anteriormente.
                return $this->faker->randomElement($users)->id;
            },
            'date_shift' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'description' => $this->faker->text,
            'price' => $this->faker->randomFloat(2, 10, 100),
            'status' => $this->faker->randomElement([0, 1]), // Puedes ajustar esto según tus necesidades.
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}