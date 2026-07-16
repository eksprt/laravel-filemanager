<?php

namespace Eksprt\FileManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Eksprt\FileManager\Traits\FileHelper;

class FileUploadFromBase64
{
    use FileHelper;

    public string $base64;

    public string $format;

    public function __construct()
    {
        $this->disk = config('filemanager.disk_name');
    }

    public function add(string $base64, string $format, string $type, Model $model): FileUploadFromBase64
    {
        $this->base64 = $base64;
        $this->format = $format;
        $this->type = $type;
        $this->model = $model;

        $this->validateModelRegisteredConversions();

        return $this;
    }

    public function toFileCollection(string $collection = ''): array
    {
        $this->collection = $collection;

        $file_name = Str::random(40).'.'.$this->format;
        $file_path = $this->getFileUploadPath() . DIRECTORY_SEPARATOR . $file_name;

        Storage::disk($this->disk)->put($file_path, $this->decodeBase64Code(), 'public');

        $file = $this->model->attachments()->create([
            'type' => $this->type,
            'file_name' => $file_name,
            'name' => $this->type,
            'mime_type' => Storage::disk($this->disk)->mimeType($file_path),
            'size' => Storage::disk($this->disk)->size($file_path),
            'disk' => $this->disk,
            'collection_name' => $this->getCollection(),
            'sort_order' => $this->model->attachments()->whereType($this->type)->count(),
        ]);

        $this->setDefaultConversions($file);

        $this->dispatchConversionJobs($file);

        return [
            'file_id' => $file->id,
            'file_name' => $file_name,
        ];
    }

    protected function decodeBase64Code()
    {
        $image = str_replace('data:image/png;base64,', '', $this->base64);
        $image = str_replace('data:image/jpeg;base64,', '', $image);
        $image = str_replace('data:image/gif;base64,', '', $image);
        $image = str_replace('data:image/webp;base64,', '', $image);
        $image = str_replace('data:image/svg+xml;base64,', '', $image);
        $image = str_replace(' ', '+', $image);

        return base64_decode($image);
    }
}
