<?php

namespace App\Socket\Controllers;

use App\Models\Server;
use Arr;
use Config;
use Exception;
use Log;
use \React\Datagram\Socket;
use React\Datagram\SocketInterface;

class ListingController extends CommonController
{
    private array $client = [
        'valid'          => false,
        'currentQueryID' => '0.0',
    ];

    protected SocketInterface $connection;
    protected string $hashedAddress;

    public function __construct(SocketInterface $connection)
    {
        $this->connection    = $connection;
        $this->hashedAddress = \Hash::make($connection->getRemoteAddress());
    }

    /**
     * On stream connection, behind the scenes it actually hooks up the rest of the events
     */
    public function onConnected(): void
    {
        Log::info("Client {$this->hashedAddress} connected");
    }

    /**
     * On error, when a ReactPHP error gets thrown, I could be handled here
     * @param  Exception  $e
     */
    public function onError(Exception $e): void
    {
        Log::info("Client received error {$e->getMessage()}");
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
        Log::info("Client {$this->hashedAddress} ended their connection");
    }

    /**
     * On data get! Handle any data incoming here
     * @param  string  $message
     * @param  string  $serverAddress
     */
    public function onData(string $message, $serverAddress): void
    {
        Log::info("Received data from client {$serverAddress}: {$message}");

        $queries  = $this->messageToArray($message);
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

        if ($response !== '') {
            $response .= '\\final\\';
        }

        $this->connection->send($response, $serverAddress);

        Log::info("Sent client {$serverAddress}: {$response}");
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
        $stateChanged = (bool) \Arr::has($query, 'statechanged');
        $response     = '';


        $gameName = Arr::get($query, 'gamename');

        $hostAddress = $this->getHostAddress($query, $serverAddress);

        try {
            $server = (new Server())->findInCache($hostAddress, $gameName);
        } catch (\RuntimeException $e) {
            $server = null;
        }

        // Update the time, and update the cache
        if ($server) {
            $server->setUpdatedAt(now());
            $server->updateInCache($hostAddress, $server->toArray());
        }

        // While normally we'd wait for a state change, some games don't seem to request one.
        // So let's always ask for status on every heartbeat.
        $response .= '\\status\\';

        // If we don't have a server by them
        if (!$server) {
            Log::info("Requested updated server info from {$serverAddress}");

            // Not all games support \\status\\ request directly from the master server (lol, Unreal Engine 1 games)
            // So use the info we've got to mark the game server down.
            $publishQuery = [
                'hostname' => 'Not Available',
                'gamename' => Arr::get($query, 'gamename'),
                'gamever'  => '1.0',
                'password' => 0,
            ];

            $this->handlePublish($publishQuery, $serverAddress);
        }

        return $response;
    }

    protected function handlePublish($query, $serverAddress): bool
    {
        $exclude_for_options = [
            'hostname',
            'hostip',
            'hostport',
            'password',
            'gamename',
            'gamever',
            'gamemode',
        ];

        $hostAddress = $this->getHostAddress($query, $serverAddress);

        $server          = new Server();
        $server->name    = Arr::get($query, 'hostname');
        $server->address = $hostAddress;

        $server->has_password = (bool) Arr::get($query, 'password', 0);
        $server->game_name    = Arr::get($query, 'gamename');
        $server->game_version = Arr::get($query, 'gamever');

        // Filter the options we already stored above
        $options = array_filter($query, function ($item) use ($exclude_for_options) {
            return !in_array($item, $exclude_for_options, true);
        }, ARRAY_FILTER_USE_KEY);

        $server->options = $options;

        $serverArray = $server->toArray();

        return $server->updateInCache($hostAddress, $serverArray);
    }

    private function getHostAddress($query, $serverAddress)
    {
        // Some games don't pass over the hostip, so default to the server trying to talk to us!
        $hostAddress = $serverAddress;

        // But some do. So if it's there, let's use it!
        if (isset($query['hostip'], $query['hostport'])) {
            $ip = Arr::get($query, 'hostip');
            $port = Arr::get($query, 'hostport');

            // Okay...some games give us the lan ip...parse the real ip from our request
            if (\Str::contains($ip, '192.168.'))
            {
                $ip = Arr::first(explode(':', $serverAddress));
            }

            $hostAddress = "{$ip}:{$port}";
        }

        return $hostAddress;
    }

}
