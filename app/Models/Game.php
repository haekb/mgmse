<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Holds the list of games displayed on this master list server
 * Used mainly to figure out what keys we need to check for the expired server counter!
 * Class Game
 * @property string $game_name
 * @property int    $server_count
 * @package App\Models
 * @mixin \Eloquent
 */
class Game extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'game_name',
        'server_count',
        'version',
    ];

    public function save(array $options = [])
    {
        if (\App::runningUnitTests())
        {
            return parent::save($options);
        }

        $wantedGame = $this->game_name ?? \Arr::get($options, 'game_name');

        $games = \Config::get('games.supported_games', []);

        if(!in_array($wantedGame, $games, true)) {
            $error = "Failed to save unsupported game {$wantedGame}!";
            \Log::error($error);
            throw new \RuntimeException($error);
        }

        return parent::save($options);
    }

}
