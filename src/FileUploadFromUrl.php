<?php

namespace Eksprt\FileManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Eksprt\FileManager\Exceptions\InvalidUrlmageException;
use Eksprt\FileManager\Traits\FileHelper;

class FileUploadFromUrl
{
    use FileHelper;

    public string $url;

    public string|null $title;

    public function __construct()
    {
        $this->disk = config('filemanager.disk_name');
    }

    public function addFileFromUrl(string $url, string $type, Model $model, string|null $title = null): FileUploadFromUrl
    {
        $this->url = $url;
        $this->type = $type;
        $this->model = $model;
        $this->title = $title;

        $this->validateUrlHasValidImage($url);
        $this->validateModelRegisteredConversions();

        return $this;
    }

    public function toFileCollection(string $collection = ''): array
    {
        $this->collection = $collection;

        $name = pathinfo($this->url, PATHINFO_BASENAME);
        $file_name = pathinfo($this->url, PATHINFO_FILENAME);
        $extension = pathinfo($this->url, PATHINFO_EXTENSION);

        $file_name = $this->makeFilenameUnique($file_name, $extension);
        $file_path = $this->getFileUploadPath() . DIRECTORY_SEPARATOR . $file_name;

        if (! $this->title) {
            $this->title = $name;
        }

        Storage::disk($this->disk)->put($file_path, file_get_contents($this->url), 'public');

        $file = $this->model->attachments()->create([
            'type' => $this->type,
            'file_name' => $file_name,
            'name' => $this->title,
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

    protected function validateUrlHasValidImage(string $file): bool
    {
        if (empty($file)) {
            throw InvalidUrlmageException::image();
        }

        $image = getimagesize($file);
        $mime = strtolower(substr($image['mime'], 0, 5));

        if ($mime !== 'image') {
            throw InvalidUrlmageException::image();
        }

        return true;
    }
}
