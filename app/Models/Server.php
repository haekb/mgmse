<?php

namespace App\Models;

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

    private const CACHE_KEY = 'models.servers';
    private const CACHE_TTL = 5; // Minutes

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
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'has_password' => 'boolean',
        'options'      => 'json',
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

        return (bool) \RedisManager::zadd($this->getCacheKey(), time(), json_encode($this->toArray()));
    }

    /**
     * Get all the cache!
     * @param  int  $min
     * @param  int  $max
     * @return Collection
     */
    public function findAllInCache($min = 0, $max = 1000): Collection
    {
        $results = \RedisManager::zrange($this->getCacheKey(), $min, $max);

        $servers = [];

        foreach ($results as $result) {
            try {
                $server = json_decode($result, true, 512, JSON_THROW_ON_ERROR);
            } catch (\ErrorException $e) {
                continue;
            }

            $servers[] = $server;
        }

        $results = self::hydrate($servers);

        return collect($results);
    }

    /**
     * Slightly slow, we have to go through all the items in the cache to find out specific server..
     * @param $address
     * @return Server
     */
    public function findInCache($address): Server
    {
        $servers = $this->findAllInCache();
        $cache   = null;

        foreach ($servers as $server) {
            try {
                $decoded_server = json_decode($server, true, 512, JSON_THROW_ON_ERROR);
            } catch (\ErrorException $e) {
                continue;
            }

            if (\Arr::get($decoded_server, 'address') === $address) {
                $cache = $decoded_server;
                break;
            }
        }

        if (!$cache) {
            throw new \RuntimeException("Could not find server {$address} in cache.");
        }

        // Fill this model instance
        $this->fill($cache);

        return $this;
    }

    /**
     * TODO: I'm probably real slow!
     * @param $address
     * @param $options
     * @return bool
     */
    public function updateInCache($address, $options): bool
    {
        $servers     = $this->findAllInCache();
        $cache       = null;
        $serverIndex = -1;

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

        // Fill this model instance
        $this->fill($options);

        if ($cache) {
            // Remove the old entry
            \RedisManager::zremRangeByRank($this->getCacheKey(), $serverIndex, $serverIndex);
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
