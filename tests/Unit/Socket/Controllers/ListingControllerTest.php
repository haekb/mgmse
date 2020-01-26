<?php

namespace Tests\Unit\Socket\Controllers;

use App\Models\Server;
use App\Socket\Controllers\ListingController;
use App\Socket\Controllers\QueryController;
use Tests\TestCase;
use Tests\Unit\Socket\Stubs\UDPSocketStub;

class ListingControllerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Before each test, clear the test cache!
        $cache_key = (new Server())->getCacheKey() . '.nolf2';
        \RedisManager::del($cache_key);
    }

    /**
     * Make sure we return nothing if they ask for nothing!
     * @dataProvider provideEmptyQueries
     * @param $query
     */
    public function testOnDataReturnsEmpty($query): void
    {
        $connection = new UDPSocketStub();
        $this->assertEmpty($connection->getData());

        $listingController = new ListingController($connection);

        // Empty query will exit early
        $listingController->onData($query, '127.0.0.1:1234');
        $this->assertEmpty($connection->getData());
    }

    /**
     * Test our good friend echo!
     * It'll just spit back what you send it.
     */
    public function testOnDataEcho(): void
    {
        $connection = new UDPSocketStub();
        $this->assertEmpty($connection->getData());

        $listingController = new ListingController($connection);

        $query = "\\echo\\hello world";

        $listingController->onData($query, '127.0.0.1:1234');
        $this->assertNotEmpty($connection->getData());
        $this->assertEquals($query, $connection->getData());
    }

    /**
     * Test out a heartbeat with no "statechange" query or actual server in our cache
     * This should return a \status\ query
     */
    public function testOnDataHeartbeatNoStateChangedNoServer(): void
    {
        $socket = new UDPSocketStub();
        $this->assertEmpty($socket->getData());

        $listingServer = new ListingController($socket);

        $query = "\\heartbeat\\27889\\gamename\\nolf2\\final\\\\queryid\\1.0";

        $listingServer->onData($query, '127.0.0.1:27889');
        $data = $socket->getData();

        $this->assertNotEmpty($data);

        // Because we don't have a server with that address, we need more info
        $this->assertEquals("\\status\\", $data);
    }

    /**
     * Test out a heartbeat with no "statechange" query
     * We have a server in cache,
     * so all we're doing here is updating the timestamp so it doesn't get removed in 5 minutes.
     */
    public function testOnDataHeartbeatNoStateChanged(): void
    {
        $socket = new UDPSocketStub();
        $this->assertEmpty($socket->getData());

        // Use make so we don't store it in db.
        $server = factory(Server::class)->make(['game_name' => 'nolf2']);
        $server->cache();

        $listingServer = new ListingController($socket);

        $query = "\\heartbeat\\27889\\gamename\\nolf2\\final\\\\queryid\\1.0";

        $listingServer->onData($query, $server->address);
        $data = $socket->getData();

        // No update
        $this->assertEmpty($data);
    }

    public function testOnDataHeartbeatStateChanged(): void
    {
        $socket = new UDPSocketStub();
        $this->assertEmpty($socket->getData());

        // Use make so we don't store it in db.
        $server = factory(Server::class)->make(['game_name' => 'nolf2']);
        $server->cache();

        $listingController = new ListingController($socket);

        $query = "\\heartbeat\\27889\\gamename\\nolf2\\statechanged\\\\final\\\\queryid\\1.0";

        $listingController->onData($query, $server->address);
        $data = $socket->getData();

        $this->assertNotEmpty($data);

        // They requested a statechange, we need more info
        $this->assertEquals("\\status\\", $data);
    }

    public function testOnDataPublish() : void
    {
        $socket = new UDPSocketStub();
        $this->assertEmpty($socket->getData());

        $listingServer = new ListingController($socket);

        $serverAddress = '192.168.1.1:27888';
        $gameName = 'nolf2';

        $query = '\\hostname\\JakeDM\\gamename\\nolf2\\hostport\\27888\\gamemode\\openplaying\\gamever\\1.0.0.4M\\gametype\\DeathMatch\\hostip\\192.168.1.1\\frag_0\\0\\mapname\\DD_02\\maxplayers\\16\\numplayers\\1\\fraglimit\\25\\options\\\\password\\0\\timelimit\\10\\ping_0\\0\\player_0\\Jake\\final\\queryid\\2.1';

        $listingServer->onData($query, $serverAddress);
        $data = $socket->getData();

        // No response
        $this->assertEmpty($data);

        $server = (new Server())->findInCache($serverAddress, $gameName);

        $this->assertNotNull($server);

        // TODO: Add more equals, address is a good indicator though.
        $this->assertEquals($serverAddress, $server->address);
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
        ];
    }
}
