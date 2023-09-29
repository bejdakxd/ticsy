<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Incident;
use App\Models\Ticket;
use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class IncidentFactory extends TicketFactory
{
    protected $model = Incident::class;

    public function definition()
    {
        return [
            'category_id' => rand(1, count(Incident::CATEGORIES)),
            'type_id' => Ticket::TYPES['incident'],
            'description' => fake()->sentence(10),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    public function existing(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'description' => fake()->sentence(10),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        });
    }
}
