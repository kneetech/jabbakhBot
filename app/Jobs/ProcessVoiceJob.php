<?php

namespace App\Jobs;

use Botflow\Contracts\IBotService;
use Botflow\Enums\Reaction;
use Botflow\Exceptions\Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessVoiceJob implements ShouldQueue
{
    use Queueable;


    public function __construct(private int $userId, private string $voiceId, private int $messageId)
    {
        //
    }

    /**
     * @throws Exception
     */
    public function handle(IBotService $botService): void
    {
        $botService->setTelegraphChat($this->userId);

        $botService->telegraph()->reactWithEmoji($this->messageId, Reaction::YES_SIR->value, true)->send();

        $tempMessageId = $botService->telegraph()
            ->reply($this->messageId)
            ->markdown('ща посмотрим чего там тебе налепетали')
            ->send()->telegraphMessageId();

        Log::info('Начинаю обработку аудиосообщения', [
            'user' => $this->userId,
            'voice' => $this->voiceId,
            'message' => $this->messageId
        ]);

        $fileInfoResponse = $botService->telegraph()->getFileInfo($this->voiceId)->send();

        Log::info('Получены данные по временной ссылке на скачвание голосового сообщения с телеграм', $fileInfoResponse->json());

        $fileUrl = sprintf(
            "https://api.telegram.org/file/bot%s/%s",
            $botService->telegraphBot()->token,
            $fileInfoResponse->json()['result']['file_path']
        );

        $tmpFileName = uniqid();

        Storage::put($tmpFileName, file_get_contents($fileUrl));

        $filename = storage_path('app/' . $tmpFileName);
        $filesize = filesize($filename);

        Log::info('Файл сохранён на сервере', ['path' => $filename, 'size' => $filesize]);

        $ch = curl_init();
        $options = [
            CURLOPT_URL => "http://185.41.160.207:8000/v1/audio/transcriptions",
            CURLOPT_HEADER => true,
            CURLOPT_POST => 1,
            CURLOPT_HTTPHEADER => ["Content-Type:multipart/form-data"],
            CURLOPT_POSTFIELDS => [
                "model" => "Systran/faster-whisper-small",
                "language" => "ru",
                "prompt" => "",
                "response_format" => "text",
                "temperature"=> "0",
                "timestamp_granularities" => "segment",
                "stream" => "false",
                "hotwords" => "string",
                "vad_filter" => "false",
                "file" => new \CURLFile($filename),
            ],
            CURLOPT_INFILESIZE => $filesize,
            CURLOPT_RETURNTRANSFER => true,
        ];
        curl_setopt_array($ch, $options);

        Log::info('Запрос к faster-whisper', $options);

        $resp = curl_exec($ch);


        if(!curl_errno($ch)) {
            $info = curl_getinfo($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($resp, 0, $header_size);
            $body = substr($resp, $header_size);

            Log::info('Полный ответ faster-whisper', $info);

            if ($info['http_code'] == 200) {
                Log::info('Faster whisper справился', ['header' => $header, 'body' => $body]);
                $botService->telegraph()->reply($this->messageId)->markdown(escape_markdown($body))->send();
            } else {
                Log::info('Faster whisper ответил ошибкой', ['header' => $header, 'body' => $body]);
                $botService->telegraph()->reply($this->messageId)->markdown(escape_markdown("нейронка, похоже упала... попробуй попозже"))->send();
            }
        } else {
            $errmsg = curl_error($ch);
            Log::info("Запрос к faster-whisper упал с ошибкой", [$errmsg]);
            $botService->telegraph()->reply($this->messageId)->markdown(escape_markdown("нейронка че-то не отвечает... попробуй попозже"))->send();
        }

        curl_close($ch);
        unlink($filename);

        $botService->telegraph()->deleteMessage($tempMessageId)->send();
    }
}
