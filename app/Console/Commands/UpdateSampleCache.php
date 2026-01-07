<?php

namespace App\Console\Commands;

use App\CachedSample;
use Illuminate\Console\Command;

class UpdateSampleCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sample:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh cached repertoire metadata';

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
        // needed here because .htaccess is not used by Artisan commands
        ini_set('memory_limit', '1024M');

        $n = CachedSample::cache();
        $message = "$n samples have been retrieved and cached.";
        echo $message;
    }
}
