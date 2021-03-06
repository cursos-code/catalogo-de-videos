<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Video;
use Faker\Generator as Faker;

$factory->define(Video::class, function (Faker $faker) {
    $rating = Video::RATING_LIST[array_rand(Video::RATING_LIST)];
    return [
        'title' => $faker->sentence(3),
        'description' => $faker->sentence(3),
        'year_launched' => rand(1910, 2021),
        'opened' => rand(0,1),
        'rating' => $rating,
        'duration' => rand(1,30)
    ];
});
