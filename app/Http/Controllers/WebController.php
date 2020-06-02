<?php


namespace App\Http\Controllers;

use App\Models\Motd;
use Response;

class WebController extends Controller
{
    protected const MOTD_CACHE_KEY = 'motd_game_id_';
    protected const MOTD_CACHE_TTL = 300; // 5 minutes in seconds.

    public function index()
    {
        return view('welcome');
    }

    public function privacy()
    {
        return view('privacy');
    }

    public function motd($game_id)
    {
        // Check if the motd is cached.
        $key = self::MOTD_CACHE_KEY . $game_id;
        $content = \Cache::get($key);

        // If the motd isn't cached, then retrieve it from the db.
        if (!$content) {
            // Grab the latest motd of a particular game id.
            $motd = Motd::where('game_id', '=', $game_id)->orderBy('created_at', 'desc')->first();
            $content = $motd->content ?? '';

            \Cache::put($key, $content, self::MOTD_CACHE_TTL);
        }

        // Make sure we're sending a text.
        $headers = [
            'Content-type' => 'text/plain',
            'Content-Length' => strlen($content)
        ];

        // Serve that text file!
        return Response::make($content, 200, $headers);
    }
}
