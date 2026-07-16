<?php

namespace Eksprt\FileManager;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Eksprt\FileManager\Traits\FileHelper;

class FileUploadFromGallery
{
    use FileHelper;

    public function __construct()
    {
        $this->disk = config('filemanager.disk_name');
    }

    public function attachGallery(array $request, string $type, Model $model)
    {
        $this->request = $request;
        $this->type = $type;
        $this->model = $model;

        $this->validateModelRegisteredConversions();

        return $this;
    }

    public function toFileCollection(string $collection = ''): bool
    {
        $this->collection = $collection;

        if (! isset($this->request[$this->type])) {
            return false;
        }

        $files = explode(',', $this->request[$this->type]);

        if (empty($files)) {
            return false;
        }

        $this->moveTempFilesToFile($files);

        return true;
    }

    protected function moveTempFilesToFile(array $files)
    {
        foreach ($files as $file) {
            $temp_path = 'temp' . DIRECTORY_SEPARATOR . 'dropzone' . DIRECTORY_SEPARATOR . $file;
            $new_path = $this->getFileUploadPath() . DIRECTORY_SEPARATOR . $file;

            Storage::disk($this->disk)->move($temp_path, $new_path);

            $file = $this->model->attachments()->create([
                'type' => $this->type,
                'file_name' => $file,
                'mime_type' => Storage::disk($this->disk)->mimeType($new_path),
                'size' => Storage::disk($this->disk)->size($new_path),
                'disk' => $this->disk,
                'collection_name' => $this->getCollection(),
                'sort_order' => $this->model->attachments()->whereType($this->type)->count(),
            ]);

            $this->setDefaultConversions($file);

            $this->dispatchConversionJobs($file);
        }
    }
}
