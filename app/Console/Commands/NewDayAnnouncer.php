<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class NewDayAnnouncer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'announce:new-day';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Announces a new day';

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
        \Log::info('Dawn of a new day');
    }
}
