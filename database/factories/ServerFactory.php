<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Server;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(Server::class, function (Faker $faker) {
    return [
        'name'         => 'Test Server',
        'address'      => "{$faker->ipv4}:12345",
        'has_password' => false,
        'game_name'    => 'nolf',
        'game_version' => '1.3.3.7',
        'status'       => Server::STATUS_OPEN,
        'options'      => '[]',
    ];
});
