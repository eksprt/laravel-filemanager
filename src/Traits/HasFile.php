<?php

namespace Eksprt\FileManager\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Eksprt\FileManager\Conversions\Conversion;
use Eksprt\FileManager\FileUpload;
use Eksprt\FileManager\FileUploadFromBase64;
use Eksprt\FileManager\FileUploadFromGallery;
use Eksprt\FileManager\FileUploadFromUrl;
use Eksprt\FileManager\Models\File;

trait HasFile
{
    public array $fileConversions = [];

    public static function bootHasFile()
    {
        static::deleting(function (Model $model) {
            $attachments = $model->attachments()->get();

            foreach ($attachments as $item) {
                $item->delete();
            }
        });
    }

    public function scopeFile(Builder $query)
    {
        return $query->with('attachments');
    }

    public function attachments()
    {
        return $this->morphMany(File::class, 'imageable')->orderBy('sort_order');
    }

    public function addFile(UploadedFile $file, string $type = 'image', string|null $title = null): FileUpload
    {
        $request = [$type => $file];

        return (new FileUpload)->addFileFromRequest($request, $type, $this, $title);
    }

    public function addFileFromRequest(string $type = 'image', string|null $title = null): FileUpload
    {
        return (new FileUpload)->addFileFromRequest(request()->toArray(), $type, $this, $title);
    }

    public function handleFileFromRequest(string $type = 'image', string|null $title = null): FileUpload
    {
        return (new FileUpload)->handleFileFromRequest(request()->toArray(), $type, $this, $title);
    }

    public function uploadFromLivewire($files, string $type = 'image', string|null $title = null)
    {
        return (new FileUpload)->uploadFromLivewire($this, $type, $files, $title);
    }

    public function attachGalleryToModelFromRequest(string $type = 'gallery'): FileUploadFromGallery
    {
        return (new FileUploadFromGallery)->attachGallery(request()->toArray(), $type, $this);
    }

    public function addFileFromUrl(string $url, string $type = 'image', string|null $title = null): FileUploadFromUrl
    {
        return (new FileUploadFromUrl)->addFileFromUrl($url, $type, $this, $title);
    }

    public function addFileFromBase64(string $base64, string $format = 'png', string $type = 'image'): FileUploadFromBase64
    {
        return (new FileUploadFromBase64)->add($base64, $format, $type, $this);
    }

    public function HasFile(string $type = 'image'): bool
    {
        $file = $this->getFile($type);

        if (! $file) {
            return false;
        }

        return true;
    }

    public function getFile(string $type = 'image'): ?File
    {
        if (! $this->relationLoaded('attachments')) {
            $this->load('attachments');
        }

        return $this->attachments
            ->first(function ($item) use ($type) {
                return $item['type'] === $type;
            });
    }

    public function getAttachments(string $type = 'gallery')
    {
        if (! $this->relationLoaded('attachments')) {
            $this->load('attachments');
        }

        return $this->attachments
            ->filter(function ($item) use ($type) {
                return $item['type'] === $type;
            });
    }

    public function getFirstFileUrl(string $type = 'image', string $conversion = 'original'): string
    {
        $file = $this->getFile($type);

        if (! $file) {
            return '';
        }

        return Storage::disk($file->disk)->url($file->getFilePath($conversion));
    }

    public function getThumbnailUrl(string $type = 'image'): string
    {
        $file = $this->getFile($type);

        if (! $file) {
            return '';
        }

        try {
            return Storage::disk($file->disk)->url($file->getFilePath('thumbnail'));
        } catch (\Throwable $th) {
            return $this->getFirstFileUrl();
        }
    }

    public function getFirstFileTitle(string $type = 'image'): string
    {
        $file = $this->getFile($type);

        if (! $file) {
            return '';
        }

        return $file->name;
    }

    public function addFileConversion(string $name): Conversion
    {
        $conversion = (new Conversion)->create($name);

        $this->fileConversions[] = $conversion;

        return $conversion;
    }

    public function registerFileConversions()
    {
    }

    public function defaultCollection(): string
    {
        return '';
    }
}
