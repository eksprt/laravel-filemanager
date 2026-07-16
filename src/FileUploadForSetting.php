<?php

namespace Eksprt\FileManager;

use Illuminate\Http\UploadedFile;
use Eksprt\FileManager\Jobs\ThumbnailConversion;
use Eksprt\FileManager\Jobs\WebpConversion;
use Eksprt\FileManager\Models\File;
use Eksprt\FileManager\Traits\FileHelper;

class FileUploadForSetting
{
    use FileHelper;

    public function __construct()
    {
        $this->disk = config('filemanager.disk_name');
    }

    public function toFileCollection(string $collection = '')
    {
        $this->collection = $collection;

        return $this;
    }

    public function handle(array $request, string $type, string $option_name)
    {
        $this->deleteOldFileIfRequested($request, $type, $option_name);

        if (! isset($request[$type])) {
            return null;
        }

        $file = $this->upload($request[$type], $type);

        return $file['file_id'];
    }

    public function upload(UploadedFile $file, string $type): array
    {
        $this->checkMaxFileUploadSize($file);

        $filename = $this->getUploadedFileUniqueName($file);

        $file->storeAs($this->getFileUploadPath(), $filename, $this->disk);

        $file = File::create([
            'type' => $type,
            'file_name' => $filename,
            'name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'disk' => $this->disk,
            'collection_name' => $this->getCollection(),
        ]);

        $this->setDefaultConversions($file);

        if (in_array($file->mime_type, $this->allowedMimeTypesForConversion())) {
            $webp_conversion = config('filemanager.webp_conversion');

            if ($webp_conversion && $file->mime_type !== 'image/webp') {
                WebpConversion::dispatch($file->id, []);
            } else {
                ThumbnailConversion::dispatch($file->id);
            }
        }

        return [
            'file_id' => $file->id,
            'file_name' => $filename,
        ];
    }

    protected function deleteOldFileIfRequested(array $request, string $type, string $option_name): void
    {
        if (! isset($request['remove_' . $type])) {
            return;
        }

        if ($request['remove_' . $type] === 'no') {
            return;
        }

        if (isset($request[$type])) {
            $this->checkMaxFileUploadSize($request[$type]);
        }

        $file = File::find(setting()->get($option_name));

        if ($file) {
            $file->delete();
        }
    }
}
