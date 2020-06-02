<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Faker\Generator as Faker;

$factory->define(\App\Models\Motd::class, function (Faker $faker) {
    return [
        'content' => $faker->text,
        'game_id' => function () {
            return factory(\App\Models\Game::class)->create()->id;
        },
    ];
});
