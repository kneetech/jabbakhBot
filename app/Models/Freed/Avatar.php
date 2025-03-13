<?php

namespace App\Models\Freed;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Storage;

/**
 * Аватар объекта
 */
class Avatar implements Arrayable
{

    private array $sizesBasePaths = [];

    public function setSize(string $name, string $fileBasePath): void
    {
        $this->sizesBasePaths[$name] = $fileBasePath;
    }

    public function sizesBasePaths(): array
    {
        return $this->sizesBasePaths;
    }

    public function toArray(): array
    {
        $sizesUrls = [];

        foreach ($this->sizesBasePaths as $sizeName => $sizeBasePath) {
            $sizesUrls[$sizeName] = Storage::url($sizeBasePath);
        }

        return $sizesUrls;
    }

    public function clear(): void
    {
        $this->sizesBasePaths = [];
    }
}
