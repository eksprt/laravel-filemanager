<?php

namespace Eksprt\FileManager;

use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Model;
use Eksprt\FileManager\Exceptions\FileMissingException;
use Eksprt\FileManager\Traits\FileHelper;

class FileUpload
{
    use FileHelper;

    public string|null $title;

    public function __construct()
    {
        $this->disk = config('filemanager.disk_name');
    }

    public function handleFileFromRequest(array $request, string $type, Model $model, string|null $title = null): FileUpload
    {
        $this->request = $request;
        $this->type = $type;
        $this->model = $model;
        $this->title = $title;

        $this->validateModelRegisteredConversions();

        return $this;
    }

    public function addFileFromRequest(array $request, string $type, Model $model, string|null $title = null): FileUpload
    {
        $this->request = $request;
        $this->type = $type;
        $this->model = $model;
        $this->title = $title;

        $this->validateModelRegisteredConversions();

        if (! isset($request[$type])) {
            throw new FileMissingException();
        }

        return $this;
    }

    public function toFileCollection(string $collection = '')
    {
        $this->collection = $collection;

        $this->deleteOldFileIfRequested();

        if (! isset($this->request[$this->type])) {
            return;
        }

        $file = $this->request[$this->type];

        return $this->upload($file);
    }

    public function uploadFromGallery(Model $model, string $type, UploadedFile $file, string $collection = '')
    {
        $this->model = $model;
        $this->type = $type;
        $this->collection = $collection;
        $this->title = null;

        $this->validateModelRegisteredConversions();

        return $this->upload($file);
    }

    public function uploadFromLivewire(Model $model, string $type, $files, string|null $title = null, string $collection = '')
    {
        $this->model = $model;
        $this->type = $type;
        $this->collection = $collection;
        $this->title = $title;

        if (! is_array($files)) {
            $files[] = $files;
        }

        $this->validateModelRegisteredConversions();

        foreach ($files as $file) {
            $this->upload($file);
        }

        return "success";
    }

    private function upload(UploadedFile $file): array
    {
        $this->checkMaxFileUploadSize($file);

        $filename = $this->getUploadedFileUniqueName($file);

        $file->storeAs($this->getFileUploadPath(), $filename, $this->disk);

        if (! $this->title) {
            $this->title = $file->getClientOriginalName();
        }

        $fileUploaded = $this->model->attachments()->create([
            'type' => $this->type,
            'file_name' => $filename,
            'name' => $this->title,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'disk' => $this->disk,
            'collection_name' => $this->getCollection(),
            'sort_order' => $this->model->attachments()->whereType($this->type)->count(),
        ]);

        $this->setDefaultConversions($fileUploaded);

        $this->dispatchConversionJobs($fileUploaded);

        return [
            'file_id' => $fileUploaded->id,
            'file_name' => $file->hashName(),
        ];
    }

    private function deleteOldFileIfRequested(): void
    {
        if (! isset($this->request['remove_' . $this->type])) {
            return;
        }

        if ($this->request['remove_' . $this->type] === 'no') {
            return;
        }

        if (isset($this->request[$this->type])) {
            $this->checkMaxFileUploadSize($this->request[$this->type]);
        }

        $this->model->attachments()->whereType($this->type)->first()->delete();
    }
}
