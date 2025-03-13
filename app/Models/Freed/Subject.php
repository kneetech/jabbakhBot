<?php

namespace App\Models\Freed;

use App\Casts\AvatarJsonCast;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int    $id
 * @property string $name
 * @property array  $properties
 * @property int    $owner_id
 * @property int    $utilizer_id
 *
 * @property Avatar $avatar
 *
 * @property User   $owner
 * @property User   $utilizer
 */
class Subject extends Model
{

    protected $table = 'subjects';

    protected $fillable = [
        'name',
        'properties',
        'owner_id',
        'utilizer_id',
        'avatar',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'avatar'     => AvatarJsonCast::class,
        ];
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'owner_id');
    }

    public function utilizer(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'utilizer_id');
    }
}
