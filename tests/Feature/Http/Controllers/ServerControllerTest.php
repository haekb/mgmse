<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Game;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ServerControllerTest extends TestCase
{

    public function testServerIndexNoGameOrPassword()
    {
        $response = $this->get('/api/v1/servers');
        $response->assertStatus(400);
    }

    public function testServerIndexNoPassword()
    {
        $game     = factory(Game::class)->create([
            'game_name'    => 'nolf2',
            'server_count' => 1,
        ]);
        $response = $this->get('/api/v1/servers?gameName=nolf2');
        $response->assertStatus(400);
    }

    public function testServerIndexNoGame()
    {
        $game     = factory(Game::class)->create([
            'game_name'    => 'nolf2',
            'server_count' => 1,
        ]);
        $response = $this->get('/api/v1/servers?password=helloworld');
        $response->assertStatus(400);
    }

    public function testServerIndex()
    {
        $game = factory(Game::class)->create([
            'game_name'    => 'nolf2',
            'server_count' => 1,
        ]);

        $server = Server::create([
            'name'         => 'Test Server',
            'address'      => '127.0.0.1:1234',
            'has_password' => false,
            'game_name'    => 'nolf2',
            'game_version' => '1.3.3.7',
            'status'       => Server::STATUS_OPEN,
        ]);

        $server->cache();

        $response = $this->get('/api/v1/servers?gameName=nolf2&password=helloworld');
        $response->assertStatus(200);

        $data = $response->decodeResponseJson();

        $this->assertCount(1, $data);
        $this->assertEquals($data[0]['address'], $server->address);
        $this->assertEquals($data[0]['name'], $server->name);
    }

    public function testServerIndexWithNoTrack()
    {
        $game = factory(Game::class)->create([
            'game_name'    => 'nolf2',
            'server_count' => 1,
        ]);

        $server = Server::create([
            'name'         => 'Test Server',
            'address'      => '127.0.0.1:1234',
            'has_password' => false,
            'game_name'    => 'nolf2',
            'game_version' => '1.3.3.7',
            'status'       => Server::STATUS_OPEN,
        ]);

        $server->cache();

        $server2 = Server::create([
            'name'         => 'Test [NT] Server',
            'address'      => '127.0.0.1:56789',
            'has_password' => false,
            'game_name'    => 'nolf2',
            'game_version' => '1.3.3.7',
            'status'       => Server::STATUS_OPEN,
        ]);

        $server2->cache();

        $response = $this->get('/api/v1/servers?gameName=nolf2&password=helloworld');
        $response->assertStatus(200);

        $data = $response->decodeResponseJson();

        // Make sure only the first server shows up!
        $this->assertCount(1, $data);
        $this->assertEquals($data[0]['address'], $server->address);
        $this->assertEquals($data[0]['name'], $server->name);
    }
}
