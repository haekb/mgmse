<?php

namespace App\Models;

use Carbon\Carbon;
use Eloquent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Class Server
 * Generally we don't actually save this model, we just use it for construction and then we cache in redis
 * @property string  $name
 * @property string  $address
 * @property boolean $has_password
 * @property array   $options
 * @property string  $game_name
 * @property string  $game_version
 * @property string  $status
 * @property Carbon  $created_at
 * @property Carbon  $updated_at
 * @package App\Models
 * @mixin Eloquent
 */
class Server extends Model
{
    // If we decide to actually store this in the db, this can be a table
    /*
     * Status':
     * openwaiting - Game hasn't started and players can join
     * closedwaiting - Game hasn't started and players cannot join
     * closedplaying - Game has started and players cannot join
     * openplaying - Game has started and players can join
     * openstaging - ?
     * closedstaging - ?
     * exiting - server is shutting down
     */
    public const STATUS_OPEN   = 'openplaying';
    public const STATUS_CLOSED = 'exiting';

    public const NO_TRACK_NAME = '[NT]';

    private const CACHE_KEY = 'models.servers';
    private const CACHE_TTL = 10; // Minutes

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'address',
        'has_password',
        'options',
        'game_name',
        'game_version',
        'status',
        'created_at',
        'updated_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'has_password' => 'boolean',
        'options'      => 'array',
    ];

    /**
     * Cache $this model!
     * @return bool
     */
    public function cache(): bool
    {
        if (!$this->address) {
            throw new \RuntimeException('Address field is required in order to cache the server model.');
        }

        return (bool) \RedisManager::zadd($this->getCacheKey().".{$this->game_name}", $this->updated_at->timestamp,
            json_encode($this->toArray()));
    }

    /**
     * Get all the cache!
     * @param  string  $gameName  Game Name
     * @param  int     $min
     * @param  int     $max
     * @return Collection
     */
    public function findAllInCache($gameName, $min = 0, $max = 10000): Collection
    {
        $results = \RedisManager::zrange($this->getCacheKey().".{$gameName}", $min, $max);

        $servers = [];

        foreach ($results as $result) {
            try {
                $server = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
            } catch (\ErrorException $e) {
                continue;
            }

            // BUG: For some reason Laravel isn't casting our array to json on a later ->toArray()
            // So do it here.
            try {
                $server['options'] = json_encode($server['options'], JSON_THROW_ON_ERROR, 512);
            } catch (\ErrorException $e) {
                $server['options'] = '[]';
            }

            $servers[] = $server;
        }

        $results = self::hydrate($servers);
        return collect($results);
    }

    /**
     * Slightly slow, we have to go through all the items in the cache to find out specific server..
     * @param $address
     * @param $gameName
     * @return Server
     */
    public function findInCache($address, $gameName): Server
    {
        $servers = $this->findAllInCache($gameName);

        $cache = null;

        foreach ($servers as $server) {
            if ($server->address === $address) {
                $cache = $server;
                break;
            }
        }

        if (!$cache) {
            throw new \RuntimeException("Could not find server {$address} in cache.");
        }

        // Fill this model instance
        $this->fill($cache->toArray());

        return $this;
    }

    /**
     * FIXME: I'm probably real slow!
     * @param $address
     * @param $options
     * @return bool
     */
    public function updateInCache($address, $options): bool
    {
        $gameName    = \Arr::get($options, 'game_name');
        $servers     = $this->findAllInCache($gameName);
        $cache       = null;
        $serverIndex = -1;
        $decoded_server = [];

        foreach ($servers as $index => $server) {
            try {
                $decoded_server = json_decode($server, true, 512, JSON_THROW_ON_ERROR);

            } catch (\ErrorException $e) {
                continue;
            }

            if (\Arr::get($decoded_server, 'address') === $address) {
                $cache       = $decoded_server;
                $serverIndex = $index;
                break;
            }
        }

        // Make sure created_at is not modified
        if (!isset($decoded_server['created_at'])) {
            $options['created_at'] = Carbon::now();
        }

        if (!isset($options['updated_at'])) {
            $options['updated_at'] = Carbon::now();
        }

        // Fill this model instance
        $this->fill($options);

        if ($cache) {
            $key = $this->getCacheKey().".{$gameName}";

            // Remove the old entry
            $itemsRemoved = \RedisManager::zremRangeByRank($key, $serverIndex, $serverIndex);

            if ($itemsRemoved === 0) {
                \Log::warning("[Server::updateInCache] Didn't remove any items for cache {$key}!");
            }
        } else {
            // Cool new entry! Update our Game count!
            $game = Game::where('game_name', '=', $gameName)->firstOrCreate(['game_name' => $gameName]);
            $game->server_count++;
            $game->save();
        }

        return $this->cache();
    }

    public function getCacheTTL(): int
    {
        return self::CACHE_TTL;
    }

    public function getCacheKey(): string
    {
        $key = self::CACHE_KEY;

        if (\App::runningUnitTests()) {
            $key .= '.testing';
        }

        return $key;
    }
}
