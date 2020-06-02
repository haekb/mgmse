<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use Faker\Generator as Faker;

$factory->define(\App\Models\Game::class, function (Faker $faker) {
    return [
        'game_name'    => $faker->bs,
        'server_count' => 0,
        'version'      => '1.0',
    ];
});
