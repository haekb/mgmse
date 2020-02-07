<?php

namespace App\Http\Controllers;

use App\Models\Server;
use Illuminate\Http\Request;

class ServerController extends Controller
{
    private const CACHE_KEY = 'json.servers';
    private const CACHE_TTL = 1;

    public function index(Request $request)
    {
        $json_api_key = \Config::get('features.json_api_key');
        if($json_api_key && $request->get('password') !== $json_api_key) {
            abort(400);
        }

        $gameName = $request->get('gameName');

        if(!$gameName) {
            abort(400);
        }

        $cache_key = self::CACHE_KEY . $gameName;

        $servers = null;//\Cache::get($cache_key, null);

        if(!$servers) {
            $servers = (new Server())->findAllInCache($gameName);

            // Filter any NO TRACK servers
            $servers = $servers->filter(function ($server) {
                return strpos($server->name, Server::NO_TRACK_NAME) === false;
            });

            \Cache::put($cache_key, $servers, self::CACHE_TTL);
        }

        return response($servers);
    }
}
