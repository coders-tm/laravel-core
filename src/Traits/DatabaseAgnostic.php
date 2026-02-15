<?php

namespace Coderstm\Traits;

use Illuminate\Support\Facades\DB;

trait DatabaseAgnostic
{
    protected function getDriver(): string
    {
        return DB::connection()->getDriverName();
    }

    protected function isSQLite(): bool
    {
        return $this->getDriver() === 'sqlite';
    }

    protected function isMySQL(): bool
    {
        return in_array($this->getDriver(), ['mysql', 'mariadb']);
    }

    protected function isPostgreSQL(): bool
    {
        return $this->getDriver() === 'pgsql';
    }

    protected function isSQLServer(): bool
    {
        return $this->getDriver() === 'sqlsrv';
    }

    protected function dbConcat(array $parts): string
    {
        return match ($this->getDriver()) {
            'sqlite', 'pgsql' => implode(' || ', $parts),
            'mysql', 'mariadb' => 'CONCAT('.implode(', ', $parts).')',
            'sqlsrv' => implode(' + ', $parts),
            default => 'CONCAT('.implode(', ', $parts).')',
        };
    }

    protected function dbGroupConcat(string $expression, string $separator = ',', bool $distinct = false): string
    {
        return match ($this->getDriver()) {
            'sqlite' => $distinct ? "group_concat(DISTINCT {$expression}, '{$separator}')" : "group_concat({$expression}, '{$separator}')",
            'mysql', 'mariadb' => $distinct ? "GROUP_CONCAT(DISTINCT {$expression} SEPARATOR '{$separator}')" : "GROUP_CONCAT({$expression} SEPARATOR '{$separator}')",
            'pgsql' => $distinct ? "string_agg(DISTINCT {$expression}::text, '{$separator}')" : "string_agg({$expression}::text, '{$separator}')",
            'sqlsrv' => "STRING_AGG({$expression}, '{$separator}')",
            default => "GROUP_CONCAT({$expression} SEPARATOR '{$separator}')",
        };
    }

    protected function dbDateDiff(string $endDate, string $startDate): string
    {
        return match ($this->getDriver()) {
            'sqlite' => "JULIANDAY({$endDate}) - JULIANDAY({$startDate})",
            'mysql', 'mariadb' => "DATEDIFF({$endDate}, {$startDate})",
            'pgsql' => "EXTRACT(DAY FROM ({$endDate} - {$startDate}))",
            'sqlsrv' => "DATEDIFF(day, {$startDate}, {$endDate})",
            default => "DATEDIFF({$endDate}, {$startDate})",
        };
    }

    protected function dbDateDiffMonths(string $endDate, string $startDate): string
    {
        return match ($this->getDriver()) {
            'sqlite' => "(JULIANDAY({$endDate}) - JULIANDAY({$startDate})) / 30",
            'mysql', 'mariadb' => "TIMESTAMPDIFF(MONTH, {$startDate}, {$endDate})",
            'pgsql' => "EXTRACT(YEAR FROM AGE({$endDate}, {$startDate})) * 12 + EXTRACT(MONTH FROM AGE({$endDate}, {$startDate}))",
            'sqlsrv' => "DATEDIFF(month, {$startDate}, {$endDate})",
            default => "TIMESTAMPDIFF(MONTH, {$startDate}, {$endDate})",
        };
    }

    protected function dbNow(): string
    {
        return match ($this->getDriver()) {
            'sqlite' => "datetime('now')",
            'mysql', 'mariadb' => 'NOW()',
            'pgsql' => 'NOW()',
            'sqlsrv' => 'GETDATE()',
            default => 'NOW()',
        };
    }

    protected function dbDateFormat(string $column, string $format = 'Y-m-d'): string
    {
        $sqliteFormat = $this->convertToSQLiteDateFormat($format);
        $mysqlFormat = $this->convertToMySQLDateFormat($format);
        $pgsqlFormat = $this->convertToPostgreSQLDateFormat($format);

        return match ($this->getDriver()) {
            'sqlite' => "strftime('{$sqliteFormat}', {$column})",
            'mysql', 'mariadb' => "DATE_FORMAT({$column}, '{$mysqlFormat}')",
            'pgsql' => "TO_CHAR({$column}, '{$pgsqlFormat}')",
            'sqlsrv' => "FORMAT({$column}, '{$format}')",
            default => "DATE_FORMAT({$column}, '{$mysqlFormat}')",
        };
    }

    protected function dbCoalesce(array $expressions): string
    {
        return 'COALESCE('.implode(', ', $expressions).')';
    }

    protected function dbIfNull(string $expression, $defaultValue): string
    {
        return match ($this->getDriver()) {
            'sqlite', 'mysql', 'mariadb' => "IFNULL({$expression}, {$defaultValue})",
            'pgsql' => "COALESCE({$expression}, {$defaultValue})",
            'sqlsrv' => "ISNULL({$expression}, {$defaultValue})",
            default => "IFNULL({$expression}, {$defaultValue})",
        };
    }

    protected function dbCast(string $expression, string $type): string
    {
        return "CAST({$expression} AS {$type})";
    }

    protected function dbBoolean(bool $value): string
    {
        return match ($this->getDriver()) {
            'sqlite' => $value ? '1' : '0',
            'mysql', 'mariadb' => $value ? 'TRUE' : 'FALSE',
            'pgsql' => $value ? 'TRUE' : 'FALSE',
            'sqlsrv' => $value ? '1' : '0',
            default => $value ? '1' : '0',
        };
    }

    protected function dbGroupConcatDistinct(string $expression, string $separator = ','): array
    {
        $isSQLite = $this->isSQLite();
        $sql = match ($this->getDriver()) {
            'sqlite' => "group_concat({$expression}, '{$separator}')",
            'mysql', 'mariadb' => "GROUP_CONCAT(DISTINCT {$expression} SEPARATOR '{$separator}')",
            'pgsql' => "string_agg(DISTINCT {$expression}::text, '{$separator}')",
            'sqlsrv' => "STRING_AGG({$expression}, '{$separator}')",
            default => "GROUP_CONCAT(DISTINCT {$expression} SEPARATOR '{$separator}')",
        };

        return ['sql' => $sql, 'use_php_unique' => $isSQLite];
    }

    private function convertToSQLiteDateFormat(string $format): string
    {
        $map = ['Y' => '%Y', 'y' => '%y', 'm' => '%m', 'd' => '%d', 'H' => '%H', 'i' => '%M', 's' => '%S', 'M' => '%m', 'D' => '%d'];

        return strtr($format, $map);
    }

    private function convertToMySQLDateFormat(string $format): string
    {
        $map = ['Y' => '%Y', 'y' => '%y', 'm' => '%m', 'd' => '%d', 'H' => '%H', 'i' => '%i', 's' => '%s', 'M' => '%b', 'D' => '%d'];

        return strtr($format, $map);
    }

    private function convertToPostgreSQLDateFormat(string $format): string
    {
        $map = ['Y' => 'YYYY', 'y' => 'YY', 'm' => 'MM', 'd' => 'DD', 'H' => 'HH24', 'i' => 'MI', 's' => 'SS', 'M' => 'Mon', 'D' => 'DD'];

        return strtr($format, $map);
    }

    protected function dbCase(array $conditions, $else = 'NULL'): string
    {
        $cases = [];
        foreach ($conditions as $condition => $result) {
            $cases[] = "WHEN {$condition} THEN {$result}";
        }

        return 'CASE '.implode(' ', $cases)." ELSE {$else} END";
    }

    protected function dbAddMonths(string $dateColumn, int $months): string
    {
        $months = (int) $months;
        $sign = $months >= 0 ? '+' : '';

        return match ($this->getDriver()) {
            'sqlite' => "datetime({$dateColumn}, '{$sign}{$months} months')",
            'mysql', 'mariadb' => "DATE_ADD({$dateColumn}, INTERVAL {$months} MONTH)",
            'pgsql' => "{$dateColumn} + INTERVAL '{$months} month'",
            'sqlsrv' => "DATEADD(MONTH, {$months}, {$dateColumn})",
            default => "DATE_ADD({$dateColumn}, INTERVAL {$months} MONTH)",
        };
    }

    protected function buildPeriodBoundariesQuery(array $periodBoundaries): ?\Illuminate\Database\Query\Builder
    {
        if (empty($periodBoundaries)) {
            return null;
        }
        $periodQuery = null;
        foreach ($periodBoundaries as $period) {
            $startQuoted = DB::connection()->getPdo()->quote($period['start']);
            $endQuoted = DB::connection()->getPdo()->quote($period['end']);
            $orderQuoted = (int) $period['order'];
            $union = DB::table(DB::raw('(SELECT 1) as dummy'))->select([DB::raw("{$startQuoted} as period_start"), DB::raw("{$endQuoted} as period_end"), DB::raw("{$orderQuoted} as period_order")]);
            if ($periodQuery === null) {
                $periodQuery = $union;
            } else {
                $periodQuery->unionAll($union);
            }
        }

        return $periodQuery;
    }

    protected function emptyQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table(DB::raw('(SELECT 1) as dummy'))->whereRaw('1 = 0');
    }
}
