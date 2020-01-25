<?php

namespace App\Socket\Controllers;

use App\Models\Server;
use Arr;
use Config;
use Exception;
use Log;
use React\Socket\ConnectionInterface;
use \React\Datagram\Socket;

class ListingController extends CommonController
{
    private array $client = [
        'valid'          => false,
        'currentQueryID' => '0.0',
    ];

    protected Socket $connection;

    public function __construct(Socket $connection)
    {
        $this->connection = $connection;
    }

    /**
     * On stream connection, behind the scenes it actually hooks up the rest of the events
     */
    public function onConnected(): void
    {
        Log::info("Client {$this->connection->getRemoteAddress()} connected");

        // Ask for validation!
        //$this->connection->send('\\secure\\');
    }

    /**
     * On error, when a ReactPHP error gets thrown, I could be handled here
     * @param  Exception  $e
     */
    public function onError(Exception $e): void
    {
        Log::info("Client recieved error {$e->getMessage()}");
    }

    /**
     * When a data stream is closed
     */
    public function onClosed(): void
    {

    }

    /**
     * When a data stream is ended
     */
    public function onEnded(): void
    {
        Log::info("Client {$this->connection->getRemoteAddress()} ended their connection");
    }

    /**
     * On data get! Handle any data incoming here
     * @param  string  $message
     */
    public function onData(string $message, $serverAddress): void
    {
        Log::info("Received data from client {$serverAddress}: {$message}");

        $queries = $this->messageToArray($message);
        $response = '';

        foreach ($queries as $query) {
            // Handle individual queries here!
            if (isset($query['heartbeat'])) {
                $response .= $this->handleHeartbeat($query, $serverAddress);
            } elseif (isset($query['hostname'])) {
                $this->handlePublish($query, $serverAddress);
            } elseif (isset($query['echo'])) {
                $response .= $this->handleEcho($query, $serverAddress);
            }
        }

        Log::info("Sent client {$this->connection->getRemoteAddress()}: {$response}");

        $sent = $this->connection->send($response, $serverAddress);
        /////
    }

    protected function handleEcho($query, $serverAddress): string
    {
        $echo = Arr::get($query, 'echo');

        \Log::info("Client {$serverAddress} wanted to echo {$echo}");

        // According to https://www.oldunreal.com/UnrealReference/IpServer.htm echo is identical

        return "\\echo\\{$echo}";
    }

    protected function handleHeartbeat($query, $serverAddress): string
    {
        $stateChanged = \Arr::get($query, 'stateChanged', false);
        $response = '';

        try {
            $server = (new Server())->findInCache($serverAddress);
        } catch (\RuntimeException $e) {
            $server = null;
        }

        // Update the time, and update the cache
        if ($server) {
            $server->setUpdatedAt(now());
            $server->updateInCache($serverAddress, $server->toArray());
        }

        // If we don't have a server by them, or if something changed on their end, mark it down that we need to poke them
        if(!$server || $stateChanged) {

            $response .= '\\status\\';

            Log::info("Requested updated server info from {$serverAddress}");
        }

        return $response;
    }

    protected function handlePublish($query, $serverAddress): bool
    {
        /*
        \hostname\Jake
         !        DM\gamename\nolf2\hostport\27888\gamemode\openplaying\gamever\1.0.0.4M\gametype\DeathMatch\hostip\192.168.1.1</>
         !        frag_0\0\mapname\DD_02\maxplayers\16\numplayers\1\fraglimit\25\options\\password\0\timelimit\10\ping_0\0\playe
         !        r_0\Jake\final\queryid\2.1
         */
        /*
        [
  "hostname" => "Jake DM"
  "gamename" => "nolf2"
  "hostport" => "27888"
  "gamemode" => "openplaying"
  "gamever" => "1.0.0.4M"
  "gametype" => "DeathMatch"
  "hostip" => "192.168.1.1"
  "frag_0" => "0"
  "mapname" => "DD_02"
  "maxplayers" => "16"
  "numplayers" => "1"
  "fraglimit" => "25"
  "options" => ""
  "password" => "0"
  "timelimit" => "10"
  "ping_0" => "0"
  "player_0" => "Jake"
  "queryid" => "10.1"
]
*/

        // Some games don't pass over the hostip, so default to the server trying to talk to us!
        // TODO: Why can't I just use serverAddress :thinking:
        $hostAddress = $serverAddress;//Arr::get($query, 'hostip', $serverAddress) . ':' . Arr::get($query, 'hostport');

        try {
            $server = (new Server())->findInCache($hostAddress);
        } catch (\RuntimeException $e) {
            $server = null;
        }

        //if(!$server) {
            $exclude_for_options = [
                'hostname',
                'hostip',
                'hostport',
                'password',
                'gamename',
                'gamever',
                'gamemode'
            ];

            $server = new Server();
            $server->name = Arr::get($query, 'hostname');
            $server->address = $hostAddress;

            $server->has_password = (bool)Arr::get($query, 'password', 0);
            $server->game_name = Arr::get($query, 'gamename');
            $server->game_version = Arr::get($query, 'gamever');

            $options = array_filter($query, function($item) use ($exclude_for_options) {
                return !in_array($item, $exclude_for_options, true);
            });

            $server->options = $options;

            $serverArray = $server->toArray();

            return $server->updateInCache($hostAddress, $serverArray);
        //}

    }

}
