<?php

namespace Tests\Unit\Socket\Controllers;

use App\Models\Server;
use App\Socket\Controllers\QueryController;
use Faker\Generator;
use Tests\TestCase;
use Tests\Unit\Socket\Stubs\ConnectionStub;

class QueryControllerTest extends TestCase
{
    public function setUp() : void
    {
        parent::setUp();
    }

    /**
     * @dataProvider provideEmptyQueries
     * @param $query
     */
    public function testOnDataReturnsEmpty($query): void
    {
        $connection = new ConnectionStub();
        $this->assertEmpty($connection->getData());

        $queryController = new QueryController($connection);

        // Empty query will exit early
        $queryController->onData($query);
        $this->assertEmpty($connection->getData());
    }

    public function testOnDataReturnsData(): void
    {
        $query = '\\gamename\\nolf2\\gamever\\1.3\\location\\0\\validate\\g3Fo6x\\final\\list\\\\gamename\\nolf2';

        $server = Server::create([
            'name'        => 'Test Server',
            'address'     => '127.0.0.1:1234',
            'has_password' => false,
            'game_name'    => 'nolf2',
            'game_version' => '1.3.3.7',
            'status'      => Server::STATUS_OPEN,
        ])->cache();

        $connection = new ConnectionStub();
        $this->assertEmpty($connection->getData());

        $queryController = new QueryController($connection);

        // 1. Empty query will exit early
        $queryController->onData($query);

        $data = $connection->getData();

        // Good enough for now.
        // This returns a binary string, which I can't figure out how to decode yet...unpack doesn't like me.
        $this->assertNotEmpty($data);
        $this->assertNotEquals("\\final\\", $data);

    }

    public function testLotsOfServersReturnProperly(): void
    {
        $faker = app(Generator::class);

        for($i = 0; $i < 1000; $i++) {
            $server = Server::create([
                'name'         => $faker->bs,
                'address'      => '127.0.0.1:' . $faker->unique()->numberBetween(100, 10000),
                'has_password' => false,
                'game_name'    => 'nolf2',
                'game_version' => '1.3.3.7',
                'status'       => Server::STATUS_OPEN,
            ])->cache();
        }

        $connection = new ConnectionStub();
        $this->assertEmpty($connection->getData());

        $gameName = 'nolf2';
        $servers = (new Server())->findAllInCache($gameName);

        $this->assertCount(1000, $servers);
    }

    /**
     * Covers the following:
     * 1. Empty query
     * 2. Query without a validate
     * 3. Query with a bad validate key
     */
    public function provideEmptyQueries(): array
    {
        return [
            [''],
            ['\\gamename\\nolf2\\gamever\\1.3\\location\\0\\final\\'],
            ['\\gamename\\nolf2\\gamever\\1.3\\location\\0\\validate\\12345\\final\\'],
        ];
    }
}
