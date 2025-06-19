<?php

namespace Coderstm\Models;

use Coderstm\Models\File;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Import extends Model
{
    use SerializeDate;

    const STATUS_PENDING = 'Pending';
    const STATUS_PROCESSING = 'Processing';
    const STATUS_COMPLETED = 'Completed';
    const STATUS_FAILED = 'Failed';

    protected $fillable = [
        'model',
        'file_id',
        'user_id',
        'status',
        'options',
        'success',
        'failed',
        'skipped',
    ];

    protected $casts = [
        'options' => 'array',
        'success' => 'array',
        'failed' => 'array',
        'skipped' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'user_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    public function addLogs($type, $line): void
    {
        $lines = $this->{$type} ?? [];
        $this->update([
            $type => array_merge($lines, [$line])
        ]);
    }

    public function getShortCodes(): array
    {
        return array_merge($this->user->getShortCodes(), [
            '{{IMPORT_MODEL}}' => class_basename($this->model),
            '{{IMPORT_STATUS}}' => $this->status,
            '{{IMPORT_SUCCESSED}}' => count($this->success ?? []),
            '{{IMPORT_FAILED}}' => count($this->failed ?? []),
            '{{IMPORT_SKIPPED}}' => count($this->skipped ?? []),
        ]);
    }

    protected static function booted()
    {
        parent::booted();
        static::creating(function ($model) {
            $model->status = static::STATUS_PENDING;
            $model->user_id = optional(user())->id;
        });
    }
}
