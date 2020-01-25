<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\InteractsWithQueue;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Thanks https://stackoverflow.com/questions/48264479/log-laravel-with-artisan-output
 * Class MessageLoggedListener
 * @package App\Listeners
 */
class MessageLoggedListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  MessageLogged  $event
     * @return void
     */
    public function handle(MessageLogged $event)
    {
        // Ignore non-console apps
        if (!app()->runningInConsole()) {
            return;
        }

        $output = new ConsoleOutput();
        $level = $event->level;

        if($level === 'debug') {
            $level = 'comment';
        } elseif ($level === 'warning') {
            $level = 'error';
        }

        $output->writeln("<{$level}> {$event->message} </{$level}>");
    }
}
