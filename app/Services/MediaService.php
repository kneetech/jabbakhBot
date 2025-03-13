<?php

namespace App\Services;

use App\Models\Freed\Avatar;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

//use Intervention\Image\Laravel\Facades\Image;

class MediaService
{

    /**
     * Функция перемещает картинку в хранилище и автоматически делает нужные размеры
     *
     * @param string $imageFilePath
     *
     * @return Avatar
     */
    public function storeAvatar(string $imageFilePath): Avatar
    {
        $imageFileExt  = pathinfo($imageFilePath, PATHINFO_EXTENSION);
        $imageFileName = pathinfo($imageFilePath, PATHINFO_FILENAME);

        $hash         = hash_file("sha512", $imageFilePath);
        $hashPrefix1  = substr($hash, 0, 2);
        $hashPrefix2  = substr($hash, 2, 2);
        $hashPrefix3  = substr($hash, 4, 3);
        $shardingPath = "avatars/$hashPrefix1/$hashPrefix2/$hashPrefix3";

        Storage::disk('public')->makeDirectory($shardingPath);

        $image = Image::read($imageFilePath);

        $avatar = new Avatar();
        foreach ($this->avatarSizes() as $avatarSizeName => $avatarSize) {
            $thumbnailFileName = sprintf(
                "%s_%s.%s",
                $imageFileName,
                $avatarSizeName,
                $imageFileExt
            );

            $image
                ->cover($avatarSize[0], $avatarSize[1])
                ->save('/tmp/' . $thumbnailFileName);

            $avatarSizeImageFile = fopen('/tmp/' . $thumbnailFileName, "r");

            Storage::disk('public')
                ->put("$shardingPath/$thumbnailFileName", $avatarSizeImageFile);

            fclose($avatarSizeImageFile);
            unlink('/tmp/' . $thumbnailFileName);

            $avatar->setSize($avatarSizeName, $shardingPath . '/' . $thumbnailFileName);
        }

        return $avatar;
    }

    protected function avatarSizes(): array
    {
        return config('services.media.userAvatarSizes', [
            'small'  => [128, 128],
            'medium' => [256, 256],
            'large'  => [512, 512],
        ]);
    }

    public function minUploadImageSizes(): array
    {
        return config('services.media.minUploadImageSizes', [128, 128]);
    }

    public function maxUploadImageSizes(): array
    {
        return config('services.media.maxUploadImageSizes', [1024, 1024]);
    }
}
