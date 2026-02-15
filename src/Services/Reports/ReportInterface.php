<?php

namespace Coderstm\Services\Reports;

use Coderstm\Models\ReportExport;
use League\Csv\Writer;

interface ReportInterface
{
    public static function getTypes(): array;

    public static function canHandle(string $type): bool;

    public function generate(Writer $csv, ReportExport $reportExport): int;

    public function getDescription(): string;

    public function validate(array $input): array;

    public function headers(): array;

    public function paginate(array $filters, int $perPage = 15, int $page = 1): array;

    public function query(array $filters);

    public function toRow($row): array;

    public function summarize(array $filters): array;
}
