<?php

namespace Database\Factories;

use App\Model\Study;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudyFactory extends Factory
{

    protected $model = Study::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->word,
            'patient_code_prefix' => $this->faker->randomNumber(5),
        ];
    }
}
