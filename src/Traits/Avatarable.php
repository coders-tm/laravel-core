<?php

namespace Coderstm\Traits;

use Coderstm\Models\File;
use Coderstm\Traits\HasMorphToOne;

trait Avatarable
{
    use HasMorphToOne;

    public function avatar()
    {
        return $this->morphToOne(File::class, 'fileable')->wherePivot('type', 'avatar');
    }

    public function attachAvatar(mixed $media)
    {
        $mediaId = $media instanceof File ? $media->id : $media;

        return $this->avatar()->sync([
            $mediaId => [
                'type' => 'avatar'
            ]
        ]);
    }

    public function detachAvatar()
    {
        return $this->avatar()->detach();
    }
}
