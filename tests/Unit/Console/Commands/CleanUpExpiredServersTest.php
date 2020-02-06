<?php

namespace Tests\Unit\Socket\Controllers;

use App\Models\Game;
use App\Models\Server;
use App\Socket\Controllers\ListingController;
use App\Socket\Controllers\QueryController;
use Carbon\Carbon;
use Faker\Generator;
use Tests\TestCase;
use Tests\Unit\Socket\Stubs\UDPSocketStub;

class CleanUpExpiredServersTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Before each test, clear the test cache!
        $cache_key = (new Server())->getCacheKey().'.nolf2';
        \RedisManager::del($cache_key);
    }

    public function testHandle()
    {
        // Generate some servers!
        $faker = app(Generator::class);

        $address = '127.0.0.1:'.$faker->unique()->numberBetween(100, 10000);
        $options = [
            'name'         => $faker->bs,
            'address'      => '127.0.0.1:'.$faker->unique()->numberBetween(100, 10000),
            'has_password' => false,
            'game_name'    => 'nolf2',
            'game_version' => '1.3.3.7',
            'status'       => Server::STATUS_OPEN,
            'updated_at'   => Carbon::now()->subMinutes(10),
        ];

        $cached = (new Server())->updateInCache($address, $options);

        $this->assertTrue($cached);
        $this->assertEquals(1, Game::count());
        $this->assertEquals(1, Game::first()->server_count);

        \Artisan::call('clean:expired-servers');

        $this->assertEquals(1, Game::count());
        $this->assertEquals(0, Game::first()->server_count);
    }
}
