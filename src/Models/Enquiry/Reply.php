<?php

namespace Coderstm\Models\Enquiry;

use Coderstm\Coderstm;
use Coderstm\Enum\AppStatus;
use Coderstm\Traits\Fileable;
use Coderstm\Models\Notification;
use Coderstm\Traits\SerializeDate;
use Coderstm\Jobs\SendPushNotification;
use Illuminate\Database\Eloquent\Model;
use Coderstm\Events\EnquiryReplyCreated;
use Illuminate\Database\Eloquent\Builder;
use Coderstm\Jobs\SendWhatsappNotification;
use Coderstm\Database\Factories\ReplyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reply extends Model
{
    use HasFactory, Fileable, SerializeDate;

    protected $dispatchesEvents = [
        'created' => EnquiryReplyCreated::class,
    ];

    protected $fillable = [
        'message',
        'enquiry_id',
        'user_type',
        'user_id',
        'seen',
        'staff_only',
    ];

    protected $with = ['media'];

    protected $casts = [
        'seen' => 'boolean',
    ];

    protected $appends = ['created_time'];

    protected $dateTimeFormat = 'd M, Y \a\t h:i a';

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
        return strpos($this->user_type, 'Admin') !== false;
    }

    public function renderNotification($type = null): Notification
    {
        $default = $this->byAdmin() ? 'user:enquiry-reply-notification' : 'admin:enquiry-reply-notification';

        $template = Notification::default($type ?? $default);
        $attachments = '';

        if (count($this->media)) {
            $attachments = "<p><b><small>Attachments</small></b>:<br>";
            foreach ($this->media as $media) {
                $attachments .= "<small><svg style=\"width:10px\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 512 512\"><path d=\"M396.2 83.8c-24.4-24.4-64-24.4-88.4 0l-184 184c-42.1 42.1-42.1 110.3 0 152.4s110.3 42.1 152.4 0l152-152c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-152 152c-64 64-167.6 64-231.6 0s-64-167.6 0-231.6l184-184c46.3-46.3 121.3-46.3 167.6 0s46.3 121.3 0 167.6l-176 176c-28.6 28.6-75 28.6-103.6 0s-28.6-75 0-103.6l144-144c10.9-10.9 28.7-10.9 39.6 0s10.9 28.7 0 39.6l-144 144c-6.7 6.7-6.7 17.7 0 24.4s17.7 6.7 24.4 0l176-176c24.4-24.4 24.4-64 0-88.4z\"/></svg><a href=\"{$media->url}\">{$media->name}</a></small><br>";
            }
            $attachments .= "</p>";
        }

        $shortCodes = [
            '{{USER_NAME}}' => optional($this->enquiry->user)->name ?? $this->enquiry->name,
            '{{USER_ID}}' => optional($this->enquiry->user)->id,
            '{{USER_FIRST_NAME}}' => optional($this->enquiry->user)->first_name,
            '{{USER_LAST_NAME}}' => optional($this->enquiry->user)->last_name,
            '{{USER_EMAIL}}' => optional($this->enquiry->user)->email ?? $this->enquiry->email,
            '{{USER_PHONE_NUMBER}}' => optional($this->enquiry->user)->phone_number ?? $this->enquiry->phone,
            '{{ENQUIRY_ID}}' => $this->enquiry_id,
            '{{ENQUIRY_URL}}' => app_url("enquiries/{$this->enquiry_id}?action=edit"),
            '{{ADMIN_ENQUIRY_URL}}' => admin_url("enquiries/{$this->enquiry_id}?action=edit"),
            '{{ENQUIRY_SUBJECT}}' => $this->enquiry->subject,
            '{{ENQUIRY_REPLY_ATTACHMENTS}}' => $attachments,
            '{{ENQUIRY_REPLY_MESSAGE}}' => $this->message,
            '{{ENQUIRY_REPLY_USER}}' => optional($this->user)->name,
        ];

        return $template->fill([
            'subject' => replace_short_code($template->subject, $shortCodes),
            'content' => replace_short_code($template->content, $shortCodes),
        ]);
    }

    public function sendPushNotify($type = null)
    {
        try {
            $template = $this->renderNotification($type ?? 'push:enquiry-reply-notification');

            SendPushNotification::dispatch($this->user, [
                'title' => $template->subject,
                'body' => html_text($template->content)
            ], [
                'route' => "/enquiries/{$this->enquiry_id}?action=edit",
                'enquiry_id' => $this->enquiry_id,
            ]);

            SendWhatsappNotification::dispatch($this->user, "{$template->subject}\n{$template->content}");
        } catch (\Exception $e) {
            //throw $e;
            report($e);
        }
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
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
                $model->enquiry->update([
                    'status' => AppStatus::STAFF_REPLIED,
                    'user_archived' => 0
                ]);
            } else {
                $model->enquiry->update([
                    'status' => AppStatus::REPLIED,
                    'is_archived' => 0
                ]);
            }
        });
        static::addGlobalScope('default', function (Builder $builder) {
            if (is_user()) {
                $builder->where('staff_only', 0);
            }
        });
    }
}
