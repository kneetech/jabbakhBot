<?php

namespace App\Jobs;

use Botflow\Contracts\IBotService;
use Botflow\Enums\Reaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DownloadVideoJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private int $userId, private string $videoUrl, private int $messageId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(IBotService $botService): void
    {
        $botService->setTelegraphChat($this->userId);
        $botService->telegraph()->reactWithEmoji($this->messageId, Reaction::YES_SIR->value, true)->send();

        $tmpFileName = '/tmp/' . uniqid() . '.mp4';

        $response = $botService->telegraph()->reply($this->messageId)->markdown(escape_markdown('Скачиваю файл...'))->send();
        $tempMessageId = $response->telegraphMessageId();

        $shellResult = shell_exec(sprintf(
            "youtube-dl -o '%s' '%s'",
            $tmpFileName,
            $this->videoUrl
        ));

        Log::info('Download video with youtube-dl', [$tmpFileName, $this->videoUrl, $shellResult]);

        if (filesize($tmpFileName) > 50 * 1024 * 1024) {

            $botService->telegraph()->deleteMessage($tempMessageId)->send();
            $response = $botService->telegraph()->reply($this->messageId)->markdown(escape_markdown('Файл слишком большой, пытаюсь сжать...'))->send();
            $tempMessageId = $response->telegraphMessageId();

            $tmpFileName2 = '/tmp/' . uniqid() . '.mp4';
            ///ffmpeg -i input.avi -vcodec libx264 -crf 24 -filter:v scale=720:-1 "output.avi"
            $shellResult = shell_exec(sprintf(
                'ffmpeg -i %s -vcodec libx264 -crf 24 -filter:v scale=480:-1 "%s"',
                $tmpFileName,
                $tmpFileName2
            ));
            Log::info('Encode video with ffmpeg', [$tmpFileName2, $shellResult]);
            unlink($tmpFileName);
            $tmpFileName = $tmpFileName2;
        }

        $botService->telegraph()->deleteMessage($tempMessageId)->send();
        if (filesize($tmpFileName) > 50 * 1024 * 1024) {
            $botService->telegraph()->reply($this->messageId)->markdown(escape_markdown('Файл всё-равно слишком большой'))->send();
        } else {
            $botService->telegraph()->reply($this->messageId)->video($tmpFileName)->send();
        }

        unlink($tmpFileName);
    }
}
