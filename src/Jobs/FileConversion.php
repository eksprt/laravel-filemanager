<?php

namespace Eksprt\FileManager\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Eksprt\FileManager\Conversions\ConversionHelper;

class FileConversion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $file_id;
    protected array $conversions;

    public function __construct(int $file_id, array $conversions)
    {
        $this->onQueue(config('filemanager.queue_name'));

        $this->file_id = $file_id;
        $this->conversions = $conversions;
    }

    public function handle()
    {
        (new ConversionHelper)->conversions($this->file_id, $this->conversions);
    }
}
