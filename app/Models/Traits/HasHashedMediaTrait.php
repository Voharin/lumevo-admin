<?php

namespace App\Models\Traits;

use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\FileAdder;
use Symfony\Component\HttpFoundation\File\UploadedFile;


trait HasHashedMediaTrait
{
    use InteractsWithMedia {
        InteractsWithMedia::addMedia as parentAddMedia;
    }

    // public function addMedia($file): FileAdder
    // {
    //     return $this->parentAddMedia($file)->usingFileName($file->hashName());
    // }

    public function addMedia(string|UploadedFile $file): FileAdder
    {
        if ($file instanceof UploadedFile) {
            return $this->parentAddMedia($file)->usingFileName($file->hashName());
        }
        return $this->parentAddMedia($file);
    }
}
