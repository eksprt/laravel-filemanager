<?php

namespace Eksprt\FileManager\Services;

use Illuminate\Support\Facades\Storage;
use Eksprt\FileManager\FileUpload;
use Eksprt\FileManager\Models\File;
use Eksprt\FileManager\Traits\FileHelper;

class DropzoneService
{
    use FileHelper;

    public function __construct()
    {
        $this->disk = config('filemanager.disk_name');
    }

    public function upload(array $request)
    {
        $this->request = $request;

        if (isset($request['model']) && ! empty($request['model'])) {
            return $this->directAssignToModel();
        }

        return $this->temporaryUpload();
    }

    public function delete(array $request)
    {
        if (! empty($request['file_id'])) {
            File::findOrFail($request['file_id'])->delete();

            return;
        }

        Storage::disk($this->disk)
            ->delete($this->temporaryPath($request['file_name']));
    }

    protected function directAssignToModel()
    {
        $modelName = "\\App\Models\\" . $this->request['model'];
        $model = $modelName::findOrFail($this->request['model_id']);
        $response = [];

        foreach ($this->request['file'] as $file) {
            $file = (new FileUpload)->uploadFromGallery(
                $model,
                $this->request['type'],
                $file,
                $this->request['collection'] ?: ''
            );

            $response[] = [
                'file_id' => $file['file_id'],
                'file_name' => $file->getClientOriginalName(),
                'new_name' => $file->hashName(),
            ];
        }

        return $response;
    }

    protected function temporaryUpload()
    {
        $response = [];

        foreach ($this->request['file'] as $file) {
            $filename = $this->getUploadedFileUniqueName($file);

            $file->storeAs($this->temporaryPath(), $filename, $this->disk);

            $response[] = [
                'file_id' => 0,
                'file_name' => $file->getClientOriginalName(),
                'new_name' => $filename,
            ];
        }

        return $response;
    }

    protected function temporaryPath(string $file_name = null)
    {
        $path = 'temp' . DIRECTORY_SEPARATOR . 'dropzone';

        if (! $file_name) {
            return $path;
        }

        return $path . DIRECTORY_SEPARATOR . $file_name;
    }
}
