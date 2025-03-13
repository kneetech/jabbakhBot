<?php

namespace App\Models\Bot\Flows;

use App\Jobs\DownloadVideoJob;
use Botflow\Contracts\CommonBotFlow;
use DefStudio\Telegraph\DTO\Message;
use Illuminate\Support\Facades\Log;

class DownloadVkVideoFlow extends CommonBotFlow
{

    public function handleChatMessage(Message $message): void
    {
        $messageText = $message->text();

        if (str_contains($messageText, 'vkvideo.ru') || str_contains($messageText, 'vk.com')) {
            if (preg_match('/video?([-\d]+)_([-\d]+)/', $messageText, $matches)) {
                Log::info('Preg match result', $matches);

                if (count($matches) == 3) {
                    $validUrl = sprintf(
                        'https://vk.com/video_ext.php?oid=%s&id=%s&hd=2&autoplay=1',
                        $matches[1],
                        $matches[2]
                    );

                    DownloadVideoJob::dispatch($message->chat()->id(), $validUrl, $message->id());
                }
            }

            if (preg_match('/clip?([-\d]+)_([-\d]+)/', $messageText, $matches)) {
                Log::info('Preg match result', $matches);

                if (count($matches) == 3) {
                    $validUrl = sprintf(
                        'https://vk.com/video_ext.php?oid=%s&id=%s&hd=2&autoplay=1',
                        $matches[1],
                        $matches[2]
                    );

                    DownloadVideoJob::dispatch($message->chat()->id(), $validUrl, $message->id());
                }
            }
        }
    }

    public function handleChannelPost(Message $message): void
    {
        $messageText = $message->text();

        if (str_contains($messageText, 'vkvideo.ru') || str_contains($messageText, 'vk.com')) {
            preg_match('/video?([-\d]+)_([-\d]+)/', $messageText, $matches);
            Log::info('Preg match result', $matches);

            if (count($matches) == 3) {
                $validUrl = sprintf(
                    'https://vk.com/video_ext.php?oid=%s&id=%s&hd=2&autoplay=1',
                    $matches[1],
                    $matches[2]
                );

                DownloadVideoJob::dispatch($message->chat()->id(), $validUrl, $message->id());
            }
        }
    }

}
