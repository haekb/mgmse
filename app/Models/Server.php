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

    public const CACHE_MAP_KEY = 'models.servers.map';
    public const CACHE_KEY = 'models.servers';
    public const CACHE_TTL = 5; // Minutes

    public const CACHE_LOCK = 'server.lock';

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
    public function cache() : bool
    {
        if (!$this->address) {
            throw new \RuntimeException('Address field is required in order to cache the server model.');
        }

        return (bool)\RedisManager::zadd($this::CACHE_KEY, time(), $this->toArray());
    }

    /**
     * Get all the cache!
     * @param  int  $min
     * @param  int  $max
     * @return Collection
     */
    public function findAllInCache($min = 0, $max = 1000): Collection
    {
        $results = \RedisManager::zrange($this::CACHE_KEY, $min, $max);
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
        $cache = null;

        foreach($servers as $server) {
            if($server->address === $address) {
                $cache = $server;
                break;
            }
        }

        if(!$cache) {
            throw new \RuntimeException("Could not find server {$address} in cache.");
        }

        // Fill this model instance
        $this->fill($cache);

        return $this;
    }
}
