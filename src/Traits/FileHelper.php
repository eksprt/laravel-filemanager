<?php

namespace Eksprt\FileManager\Traits;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Eksprt\FileManager\Exceptions\FileSizeTooBigException;
use Eksprt\FileManager\Exceptions\InvalidConversionException;
use Eksprt\FileManager\Jobs\FileConversion;
use Eksprt\FileManager\Jobs\ThumbnailConversion;
use Eksprt\FileManager\Jobs\WebpConversion;
use Eksprt\FileManager\Models\File;

trait FileHelper
{
    public string $type;
    public string $collection;
    public string $disk;
    public bool $without_conversions = false;
    public array $request;
    public $model;

    public function useDisk(string $disk)
    {
        $this->disk = $disk;

        return $this;
    }

    public function withoutConversions(bool $value)
    {
        $this->without_conversions = $value;

        return $this;
    }

    protected function getFileUploadPath(): string
    {
        return $this->getCollection() . DIRECTORY_SEPARATOR . 'original';
    }

    protected function getCollection(): string
    {
        if ($this->collection) {
            return $this->collection;
        }

        return $this->getCollectionFromModel();
    }

    protected function getCollectionFromModel(): string
    {
        if (! $this->model) {
            return '';
        }

        $collection = $this->model->defaultCollection();

        return Str::kebab($collection);
    }

    protected function setDefaultConversions(File $file)
    {
        $conversions = [
            'original' => $file->getFilePath(),
            'thumbnail' => $file->getFilePath(),
        ];

        $file->conversions = $conversions;

        $file->save();
    }

    protected function checkMaxFileUploadSize(UploadedFile $file)
    {
        if ($file->getSize() > config('filemanager.max_file_size')) {
            throw new FileSizeTooBigException();
        }
    }

    protected function validateModelRegisteredConversions(): void
    {
        if ($this->without_conversions) {
            return;
        }

        $this->model->registerFileConversions();

        if (empty($this->model->fileConversions)) {
            return;
        }

        foreach ($this->model->fileConversions as $conversion) {
            if (! property_exists($conversion, 'width')) {
                throw InvalidConversionException::width();
            }

            if (! property_exists($conversion, 'height')) {
                throw InvalidConversionException::height();
            }
        }
    }

    protected function dispatchConversionJobs(File $file)
    {
        if (! in_array($file->mime_type, $this->allowedMimeTypesForConversion())) {
            return;
        }

        $webp_conversion = config('filemanager.webp_conversion');

        if ($webp_conversion && $file->mime_type !== 'image/webp') {
            $fileConversions = $this->model->fileConversions;

            if ($this->without_conversions) {
                $fileConversions = [];
            }

            WebpConversion::dispatch($file->id, $fileConversions);

            return;
        }

        ThumbnailConversion::dispatch($file->id);

        if ($this->without_conversions) {
            return;
        }

        if (empty($this->model->fileConversions)) {
            return;
        }

        FileConversion::dispatch($file->id, $this->model->fileConversions);
    }

    protected function allowedMimeTypesForConversion()
    {
        return ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    }

    protected function getUploadedFileUniqueName(UploadedFile $file)
    {
        $originalName = $file->getClientOriginalName();
        $filename = pathinfo($originalName, PATHINFO_FILENAME);

        return $this->makeFilenameUnique($filename, $file->getClientOriginalExtension());
    }

    protected function makeFilenameUnique(string $filename, string $extension)
    {
        $filename = Str::slug($filename);
        $filename = Str::limit($filename, 200, '');

        return $filename . '_' . time() . '.' . $extension;
    }
}
