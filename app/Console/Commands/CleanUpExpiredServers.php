<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\Server;
use Illuminate\Console\Command;

/**
 * Performs a scored set range removal,
 * see https://redis.io/commands/zremrangebyscore for more information!
 * Class CleanUpExpiredServers
 * @package App\Console\Commands
 */
class CleanUpExpiredServers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:expired-servers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans up expired servers from our cache';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $games = Game::where('server_count', '>', 0)->get();

        foreach ($games as $game) {
            $cache_key   = (new Server())->getCacheKey().".{$game}";
            $cache_ttl   = (new Server())->getCacheTTL();
            $expire_time = now()->subMinutes($cache_ttl)->timestamp;

            $removed = \RedisManager::zremrangebyscore($cache_key, '-inf', $expire_time);

            if ($removed > 0) {
                \Log::info("[CleanUpExpiredServers::handle] Removed {$removed} expired servers.");

                // Ok modify the server count with the amount we removed!
                $game->server_count -= $removed;
                $game->save();
            }

        }
    }
}
