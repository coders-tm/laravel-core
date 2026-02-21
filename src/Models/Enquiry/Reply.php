<?php

namespace Coderstm\Models\Enquiry;

use Coderstm\Coderstm;
use Coderstm\Database\Factories\ReplyFactory;
use Coderstm\Enum\AppStatus;
use Coderstm\Events\EnquiryReplyCreated;
use Coderstm\Models\Notification;
use Coderstm\Traits\Fileable;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Reply extends Model
{
    use Fileable, HasFactory, SerializeDate;

    protected $dispatchesEvents = ['created' => EnquiryReplyCreated::class];

    protected $fillable = ['message', 'enquiry_id', 'user_type', 'user_id', 'seen', 'staff_only'];

    protected $with = ['media'];

    protected $casts = ['seen' => 'boolean'];

    protected $appends = ['created_time'];

    public function getCreatedTimeAttribute()
    {
        return $this->created_at->format('H:i');
    }

    public function enquiry()
    {
        return $this->belongsTo(Coderstm::$enquiryModel);
    }

    public function user()
    {
        return $this->morphTo()->withOnly([]);
    }

    public function scopeUnseen($query)
    {
        return $query->where('seen', 0);
    }

    public function byAdmin(): bool
    {
        return str_contains($this->user_type, 'Admin');
    }

    public function renderNotification($type = null): Notification
    {
        $default = $this->byAdmin() ? 'user:enquiry-reply-notification' : 'admin:enquiry-reply-notification';
        $template = Notification::default($type ?? $default);
        $rendered = $template->render(['user' => $this->user ? $this->user->getShortCodes() : ['name' => 'Guest'], 'enquiry' => $this->enquiry->getShortCodes(), 'reply' => $this->getShortCodes()]);

        return $template->fill(['subject' => $rendered['subject'], 'content' => $rendered['content']]);
    }

    public function renderPushNotification($type = null)
    {
        $template = $this->renderNotification($type ?? 'push:enquiry-reply-notification');

        return optional((object) ['subject' => $template->subject, 'content' => html_text($template->content), 'whatsappContent' => html_text("{$template->subject}\n{$template->content}"), 'data' => ['route' => user_route("/enquiries/{$this->enquiry_id}?action=edit"), 'enquiry_id' => (string) $this->enquiry_id]]);
    }

    public function getShortCodes(): array
    {
        return ['message' => $this->message, 'user' => $this->user ? $this->user->getShortCodes() : ['name' => 'Guest'], 'attachments' => $this->media->map(function ($file) {
            return ['name' => $file->name, 'url' => $file->url];
        })->toArray()];
    }

    protected static function newFactory()
    {
        return ReplyFactory::new();
    }

    protected static function booted()
    {
        parent::booted();
        static::creating(function ($model) {
            if ($model->byAdmin()) {
                $model->seen = true;
            }
        });
        static::created(function ($model) {
            if ($model->staff_only) {
                return false;
            }
            if ($model->byAdmin()) {
                $model->enquiry->update(['status' => AppStatus::STAFF_REPLIED, 'user_archived' => 0]);
            } else {
                $model->enquiry->update(['status' => AppStatus::REPLIED, 'is_archived' => 0]);
            }
        });
        static::addGlobalScope('default', function (Builder $builder) {
            if (Auth::guard('users')->hasUser()) {
                $builder->where('staff_only', 0);
            }
        });
    }
}
