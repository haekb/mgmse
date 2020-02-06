<?php

namespace App\Socket\Controllers;

use App\Models\Server;
use Arr;
use Config;
use Exception;
use Log;
use React\Socket\ConnectionInterface;

/**
 * Handles typical MasterServer-like queries,
 * - Client requests a list of servers
 * - Server (that's us!) sends over the list of servers
 * Class QueryController
 * @package App\Socket\Controllers
 * @property array               $client
 * @property ConnectionInterface $connection
 */
class QueryController extends CommonController
{
    private array $client = [
        'valid'          => false,
        'currentQueryID' => '0.0',
    ];

    protected ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
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
        $this->connection->write('\\secure\\');
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
        Log::info("Client {$this->connection->getRemoteAddress()} ended their connection");
    }

    /**
     * On data get! Handle any data incoming here
     * @param  string  $message
     */
    public function onData(string $message): void
    {
        Log::info("Received data from client {$this->connection->getRemoteAddress()}");
        Log::debug("DATA: {$message}");


        $queries = $this->messageToArray($message);

        $response = '';

        // For some responses, we always want to include the final!
        $forceFinal = false;

        foreach ($queries as $query) {
            // Handle individual queries here!
            if (isset($query['validate'])) {
                $result = $this->handleIdentify($query);

                // If they failed the validation, then stop here!
                if (!$result) {
                    $this->connection->close();
                    return;
                }

            } elseif (isset($query['list'])) {
                $response .= $this->handleList($query, $response);
                $forceFinal = true;
            } elseif (isset($query['queryid'])) {
                $this->handleQueryID($query);
            }
        }

        if ($response !== '' || $forceFinal) {
            $security = '';//"\\secure\\TXKOAT";
            $response = $security.$response."\\final\\";
        }

        Log::info("Sent client {$this->connection->getRemoteAddress()}: {$response}");

        $sent = $this->connection->write($response);
    }

    protected function handleIdentify($query): bool
    {
        try {
            $encoded_query = json_encode($query, JSON_THROW_ON_ERROR, 512);
        } catch (\ErrorException $exception) {
            \Log::warning('[QueryController::onData] Could not json encode query! Data will be invalid.');
            $encoded_query = '! COULD NOT ENCODE !';
        }

        // Not used right now!
        $validates = Config::get('games.keys', []);
        $gameKey  = Arr::get($query, 'validate');

        // Validate the request - For now just validate that they pass something. Not really worth it to decode this stuff.
        if (!$this->client['valid'] && !$gameKey) {
            \Log::warning("[QueryController::onData] Client not valid! Data: {$encoded_query}");
            return false;
        }

        // Okie, we're good here!
        $this->client['valid'] = true;

        return true;
    }

    protected function handleList($query, $response)
    {
        $gameName = Arr::get($query, 'gamename');
        $servers = (new Server())->findAllInCache($gameName);

        foreach($servers as $server) {
            $arr = explode(':', $server->address);
            // Shove the ip address in, and make the port big endian
            $processed_server = [$this->packIP($arr[0]), pack('n', $arr[1])];
            $response         .= implode('', $processed_server);
        }

        return $response;
    }

    protected function handleQueryID($query) : void
    {
        $queryIdRequest                 = Arr::last($query);
        $this->client['currentQueryID'] = Arr::get($queryIdRequest, 'queryid', '1.1');
    }


}
