<?php

namespace Coderstm\Models;

use Coderstm\Coderstm;
use Coderstm\Database\Factories\ReportExportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ReportExport extends Model
{
    use HasFactory;

    protected $fillable = ['admin_id', 'type', 'status', 'file_name', 'file_path', 'file_size', 'total_records', 'filters', 'metadata', 'error_message', 'started_at', 'completed_at', 'expires_at'];

    protected $casts = ['filters' => 'array', 'metadata' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'expires_at' => 'datetime'];

    public function admin()
    {
        return $this->belongsTo(Coderstm::$adminModel);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['pending', 'processing']);
    }

    public function fileExists(): bool
    {
        return $this->file_path && Storage::exists($this->file_path);
    }

    public function getFileUrlAttribute(): ?string
    {
        if (! $this->fileExists()) {
            return null;
        }

        return Storage::url($this->file_path);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        if (! $this->file_size) {
            return 'N/A';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2).' '.$units[$unit];
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing', 'started_at' => now()]);
    }

    public function markAsCompleted(string $filePath, int $fileSize, int $totalRecords): void
    {
        $this->update(['status' => 'completed', 'file_path' => $filePath, 'file_size' => $fileSize, 'total_records' => $totalRecords, 'completed_at' => now(), 'expires_at' => now()->addDays(7)]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update(['status' => 'failed', 'error_message' => $errorMessage, 'completed_at' => now()]);
    }

    public function deleteFile(): bool
    {
        if ($this->file_path && Storage::exists($this->file_path)) {
            return Storage::delete($this->file_path);
        }

        return false;
    }

    public function scopeForAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<', now())->whereNotNull('expires_at');
    }

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($export) {
            $export->deleteFile();
        });
    }

    protected static function newFactory(): ReportExportFactory
    {
        return ReportExportFactory::new();
    }
}
