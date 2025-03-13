<?php

namespace App\Casts;

use App\Models\Freed\Avatar;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class AvatarJsonCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param Model  $model
     * @param string $key
     * @param string|null  $value
     * @param array  $attributes
     *
     * @return Avatar
     */
    public function get($model, string $key, $value, array $attributes)
    {
        $avatar = new Avatar();

        $sizes = json_decode($value, true);

        if (is_array($sizes)) {
            foreach ($sizes as $sizeName => $sizeImageBasePath) {
                $avatar->setSize($sizeName, $sizeImageBasePath);
            }
        }

        return $avatar;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param Model  $model
     * @param string $key
     * @param Avatar $value
     * @param array  $attributes
     *
     * @return string
     */
    public function set($model, string $key, $value, array $attributes)
    {
        if (!empty($value)) {
            return json_encode($value->sizesBasePaths(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            return null;
        }
    }
}
