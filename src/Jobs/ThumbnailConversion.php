<?php

namespace Eksprt\FileManager\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Eksprt\FileManager\Conversions\ConversionHelper;

class ThumbnailConversion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $file_id;

    public function __construct(int $file_id)
    {
        $this->onQueue(config('filemanager.queue_name'));

        $this->file_id = $file_id;
    }

    public function handle()
    {
        (new ConversionHelper)->generateThumbnailConversion($this->file_id);
    }
}
