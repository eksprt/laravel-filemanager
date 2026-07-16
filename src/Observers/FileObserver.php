<?php

namespace Eksprt\FileManager\Observers;

use Illuminate\Support\Facades\Storage;
use Eksprt\FileManager\Models\File;

class FileObserver
{
    public function deleted(File $file)
    {
        Storage::disk($file->disk)->delete($file->getConversionPath('original'));

        $this->decrementSortOrder($file);

        if (empty($file->conversions)) {
            return;
        }

        foreach ($file->conversions as $conversion) {
            Storage::disk($file->disk)->delete($conversion);
        }
    }

    protected function decrementSortOrder(File $file)
    {
        $imageable = $file->imageable;

        if (! $imageable) {
            return;
        }

        $imageable->attachments()
            ->whereType($file->type)
            ->where('sort_order', '>', $file->sort_order)
            ->decrement('sort_order');
    }
}