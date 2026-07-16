<?php

namespace Eksprt\FileManager\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Eksprt\FileManager\Conversions\ConversionHelper;

class WebpConversion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $file_id;

    protected array $file_conversions;

    public function __construct(int $file_id, array $file_conversions)
    {
        $this->onQueue(config('filemanager.queue_name'));

        $this->file_id = $file_id;
        $this->file_conversions = $file_conversions;
    }

    public function handle()
    {
        (new ConversionHelper)->convertOriginalImageToWebp($this->file_id, $this->file_conversions);
    }
}
