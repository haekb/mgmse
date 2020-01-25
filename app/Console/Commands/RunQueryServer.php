<?php

namespace App\Console\Commands;

use App\Socket\Controllers\QueryController;
use Exception;
use Illuminate\Console\Command;
use React\EventLoop\Factory;
use React\Socket\Server;

class RunQueryServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:query-server {--port=28900}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the query socket server (TCP)';

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
        // Ports
        // 28900 - GameSpy v1 (NOLF 1)
        // 28910 - GameSpy v2? (Contract Jack)
        $port = $this->option('port');

        $loop = Factory::create();

        $socket = new Server("127.0.0.1:{$port}", $loop, ['tcp' => ['so_reuseport' => true]]);

        $socket->on('connection', function (\React\Socket\ConnectionInterface $connection) {

            $masterServer = new QueryController($connection);

            $masterServer->onConnected();

            $connection->on('end', function () use ($masterServer) {
                $masterServer->onEnded();
            });

            $connection->on('error', function (Exception $e) use ($masterServer) {
                $masterServer->onError($e);
            });

            $connection->on('close', function () use ($masterServer) {
                $masterServer->onClosed();
            });

            $connection->on('data', function ($message) use ($masterServer) {
                $masterServer->onData($message);
            });
        });

        $this->info("Query Server tcp started on port {$port}");

        $loop->run();
    }
}
