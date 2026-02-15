<?php

namespace Coderstm\Jobs;

use Coderstm\Exceptions\ImportFailedException;
use Coderstm\Exceptions\ImportSkippedException;
use Coderstm\Models\Import;
use Coderstm\Notifications\ImportCompletedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class ProcessCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Import $import;

    public string $model;

    public string $filePath;

    public array $options;

    public function __construct(Import $import)
    {
        $this->import = $import;
        $this->model = $import->model;
        $this->filePath = $import->file->path();
        $this->options = $import->options;
    }

    public function handle(): void
    {
        $csv = Reader::createFromPath($this->filePath, 'r');
        $csv->setHeaderOffset(0);
        $csv->setDelimiter(',');
        $csvHeaders = array_map('trim', $csv->getHeader());
        $mappedHeaders = $this->model::getMappedAttributes();
        $finalHeaders = [];
        foreach ($csvHeaders as $header) {
            if (isset($mappedHeaders[$header])) {
                $finalHeaders[] = $mappedHeaders[$header];
            } else {
                $finalHeaders[] = $header;
            }
        }
        $this->import->update(['status' => Import::STATUS_PROCESSING]);
        DB::beginTransaction();
        try {
            foreach ($csv->getRecords($finalHeaders) as $key => $row) {
                try {
                    $this->model::createFromCsv($row, $this->options);
                    $this->import->addLogs('success', $key);
                } catch (\Throwable $e) {
                    if ($e instanceof ImportSkippedException) {
                        $this->import->addLogs('skipped', $key);
                    } elseif ($e instanceof ImportFailedException) {
                        $this->import->addLogs('failed', $key);
                    } else {
                        throw $e;
                    }
                }
            }
            DB::commit();
            $this->import->update(['status' => Import::STATUS_COMPLETED]);
            admin_notify(new ImportCompletedNotification($this->import));
        } catch (\Throwable $e) {
            $this->import->update(['status' => Import::STATUS_FAILED]);
            DB::rollback();
            throw $e;
        }
    }
}
