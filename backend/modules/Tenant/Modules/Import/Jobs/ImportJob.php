<?php

namespace Modules\Tenant\Modules\Import\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Tenant\Modules\Import\Repositories\ImportRepository;

class ImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $import;
    protected $key;
    protected $data;

    public function __construct($import, $key, $data)
    {
        $this->import = $import;
        $this->key = $key;
        $this->data = $data;
    }

    public function handle(): void
    {
        try {
            ImportRepository::executeJob($this->import, $this->key, $this->data);
        } catch (Exception $e) {
            logger($e->getMessage());
        }
    }
}
