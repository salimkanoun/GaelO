<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Patient;
use Faker\Generator as Faker;

$factory->define(Patient::class, function (Faker $faker) {
    return [
        'code'=>$faker->unique()->randomNumber,
        'first_name'=>$faker->word,
        'last_name'=>$faker->word,
        'gender'=>$faker->randomElement(['M', 'F']),
        'birth_day'=>$faker->randomNumber,
        'birth_month'=>$faker->randomNumber,
        'birth_year'=>$faker->randomNumber,
        'registration_date'=>now(),
        'investigator_name'=>$faker->word,
        'center_code'=>$faker->unique()->randomNumber,
        'study_name'=>$faker->word,
        'withdraw'=>$faker->randomElement([true, false]),
        'withdraw_reason'=>$faker->word,
        'withdraw_date'=>now()
    ];

});