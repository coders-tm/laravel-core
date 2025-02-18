<?php

namespace Coderstm\Jobs;

use League\Csv\Reader;
use Coderstm\Models\Import;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Coderstm\Exceptions\ImportFailedException;
use Coderstm\Exceptions\ImportSkippedException;
use Coderstm\Notifications\ImportCompletedNotification;

class ProcessCsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Import $import;
    public string $model;
    public string $filePath;
    public array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(Import $import)
    {
        $this->import = $import;
        $this->model = $import->model;
        $this->filePath = $import->file->path();
        $this->options = $import->options;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $csv = Reader::createFromPath($this->filePath, 'r');
        $csv->setHeaderOffset(0);
        $csv->setDelimiter(',');

        // Normalize CSV headers to remove newlines
        $csvHeaders = array_map('trim', $csv->getHeader());
        $mappedHeaders = $this->model::getMappedAttributes();

        // Map $headers from $mapped
        $finalHeaders = [];
        foreach ($csvHeaders as $header) {
            if (isset($mappedHeaders[$header])) {
                $finalHeaders[] = $mappedHeaders[$header];
            } else {
                $finalHeaders[] = $header;
            }
        }

        $this->import->update(['status' => Import::STATUS_PROCESSING]);

        // Begin a database transaction
        DB::beginTransaction();

        try {
            foreach ($csv->getRecords($finalHeaders) as $key => $row) {
                try {
                    $this->model::createFromCsv($row, $this->options);
                    $this->import->addLogs("success", $key);
                } catch (\Exception $e) {
                    if ($e instanceof ImportSkippedException) {
                        $this->import->addLogs("skipped", $key);
                    } else if ($e instanceof ImportFailedException) {
                        $this->import->addLogs("failed", $key);
                    } else {
                        throw $e;
                    }
                }
            }

            // Commit the transaction if all records are successfully processed
            DB::commit();

            // Update import status to completed
            $this->import->update(['status' => Import::STATUS_COMPLETED]);
            admin_notify(new ImportCompletedNotification($this->import));
        } catch (\Exception $e) {
            // Update import status to failed
            $this->import->update(['status' => Import::STATUS_FAILED]);

            // Rollback the transaction in case of an error
            DB::rollback();

            throw $e;
        }
    }
}
