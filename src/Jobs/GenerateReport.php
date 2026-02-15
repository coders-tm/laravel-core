<?php

namespace Coderstm\Jobs;

use Coderstm\Models\ReportExport;
use Coderstm\Services\Reports\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class GenerateReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ReportExport $reportExport) {}

    public function handle(): void
    {
        try {
            $this->reportExport->markAsProcessing();
            $fileName = $this->generateFileName();
            $filePath = "reports/{$fileName}";
            $csv = Writer::createFromFileObject(new \SplTempFileObject);
            $service = ReportService::resolve($this->reportExport->type);
            $totalRecords = $service->generate($csv, $this->reportExport);
            $content = $csv->toString();
            Storage::put($filePath, $content);
            $fileSize = strlen($content);
            $this->reportExport->markAsCompleted($filePath, $fileSize, $totalRecords);
        } catch (\Throwable $e) {
            $this->reportExport->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    public static function getSupportedTypes(): array
    {
        return ReportService::all();
    }

    public static function supportsType(string $type): bool
    {
        return ReportService::has($type);
    }

    protected function generateFileName(): string
    {
        $timestamp = now()->format('Y-m-d_His');

        return "{$this->reportExport->type}_export_{$timestamp}_{$this->reportExport->id}.csv";
    }
}
