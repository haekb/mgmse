<?php

namespace App\Console\Commands;

use App\Socket\Controllers\ListingController;
use Illuminate\Console\Command;
use \React\EventLoop\Factory;
use \React\Datagram\Factory as UDPFactory;
use \React\Datagram\Socket;
use \React\Socket\Server;

class RunListingServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:listing-server {--port=27900}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the server listing socket server (UDP)';

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
        $port = $this->option('port');

        $loop = Factory::create();
        $factory = new UDPFactory($loop);

        $factory->createServer("127.0.0.1:{$port}")->then(function (Socket $server) {

            $publishingServer = new ListingController($server);

            $server->on('connect', function () use ($publishingServer) {
                $publishingServer->onConnected();
            });

            $server->on('listening', function () use ($publishingServer) {
                $publishingServer->onConnected();
            });

            $server->on('error', function (\Exception $e) use ($publishingServer) {
                $publishingServer->onError($e);
            });

            $server->on('close', function () use ($publishingServer) {
                $publishingServer->onClosed();
            });

            $server->on('message', function($message, $serverAddress, $server) use ($publishingServer) {
                $publishingServer->onData($message, $serverAddress);
            });
        });

        $this->info("Listing Server udp started on port {$port}");

        $loop->run();
    }
}
