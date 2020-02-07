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
        $gameName = $request->get('gameName');

        if(!$gameName) {
            abort(400);
        }

        $cache_key = self::CACHE_KEY . $gameName;

        $servers = \Cache::get($cache_key, null);

        if(!$servers) {
            $servers = (new Server())->findAllInCache($gameName);
            \Cache::put($cache_key, $servers, self::CACHE_TTL);
        }

        return response($servers);
    }
}
