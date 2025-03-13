<?php

namespace App\Console\Commands;

use App\Jobs\DownloadVideoJob;
use App\Jobs\ProcessVoiceJob;
use Illuminate\Console\Command;

class ProcessVideoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-video {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testing video download';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');
        DownloadVideoJob::dispatch($url);
//        $tmpFileName = '/tmp/' . uniqid() . '.mp4';
//
//        shell_exec(sprintf(
//            "youtube-dl -o '%s' '%s'",
//            $tmpFileName,
//            $url
//        ));
    }
}
