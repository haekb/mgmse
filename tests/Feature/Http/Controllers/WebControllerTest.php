<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Game;
use App\Models\Server;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class WebControllerTest extends TestCase
{

    /**
     * Test MOTD is empty when there is no entry in the db
     */
    public function testMotdIsEmptyWhenThereIsNoEntry(): void
    {
        // Create 1 game
        $game = factory(\App\Models\Game::class)->create();

        $response = $this->get("/{$game->id}/motd");
        $response->assertStatus(200);

        $data = $response->getContent();

        $this->assertEmpty($data);
    }

    public function testMotdHasContent(): void
    {
        // Create 1 game
        $game = factory(\App\Models\Game::class)->create();
        $motd = factory(\App\Models\Motd::class)->create(['game_id' => $game->id]);

        $response = $this->get("/{$game->id}/motd");
        $response->assertStatus(200);

        $data = $response->getContent();

        $this->assertEquals($motd->content, $data);
    }

    public function testMotdGetsLatest(): void
    {
        // Create 1 game
        $game = factory(\App\Models\Game::class)->create();

        // This is not today! So this will never show up.
        $motdOutdated = factory(\App\Models\Motd::class)->create([
            'game_id'    => $game->id,
            'created_at' => Carbon::create(1991, 12, 04),
        ]);

        // This is today! Which is probably after 1991..
        $motd = factory(\App\Models\Motd::class)->create(['game_id' => $game->id]);

        $response = $this->get("/{$game->id}/motd");
        $response->assertStatus(200);

        $data = $response->getContent();

        $this->assertEquals($motd->content, $data);
    }

    /**
     * Test to make sure we can have nullable version
     */
    public function testGameHasNoVersion(): void
    {
        $game = factory(\App\Models\Game::class)->create(['version' => null]);

        $response = $this->get("/{$game->id}/version");
        $response->assertStatus(200);

        $data = $response->getContent();

        $this->assertEmpty($data);
    }

    /**
     * Test to make sure we can have ACTUAL stuff and it returns correctly!
     */
    public function testGameHasVersion(): void
    {
        $game = factory(\App\Models\Game::class)->create(['version' => '3.14']);

        $response = $this->get("/{$game->id}/version");
        $response->assertStatus(200);

        $data = $response->getContent();

        $this->assertEquals($game->version, $data);
    }

}
