<?php

namespace Coderstm\Models;

use Coderstm\Coderstm;
use Coderstm\Traits\Core;
use Coderstm\Enum\AppStatus;
use Coderstm\Traits\Fileable;
use Coderstm\Models\Enquiry\Reply;
use Illuminate\Support\Facades\DB;
use Coderstm\Events\EnquiryCreated;
use Coderstm\Jobs\SendPushNotification;
use Illuminate\Database\Eloquent\Model;
use Coderstm\Jobs\SendWhatsappNotification;

class Enquiry extends Model
{
    use Core, Fileable;

    protected $dispatchesEvents = [
        'created' => EnquiryCreated::class,
    ];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'seen',
        'is_archived',
        'user_archived',
        'source',
    ];

    protected $with = ['last_reply.user', 'user'];

    protected $appends = ['has_unseen'];

    protected $withCount = ['unseen'];

    protected $dateTimeFormat = 'd M, Y \a\t h:i a';

    protected $casts = [
        'status' => AppStatus::class,
        'seen' => 'boolean',
        'is_archived' => 'boolean',
        'user_archived' => 'boolean',
        'source' => 'boolean',
    ];

    public function getHasUnseenAttribute()
    {
        return $this->unseen_count > 0 || !$this->seen;
    }

    public function getNameAttribute($value)
    {
        if ($this->user) {
            return $this->user->name;
        }
        return $value;
    }

    public function getPhoneAttribute($value)
    {
        if ($this->user) {
            return $this->user->phone_number;
        }
        return $value;
    }

    public function user()
    {
        return $this->belongsTo(Coderstm::$userModel, 'email', 'email')->withOnly([]);
    }

    public function replies()
    {
        return $this->hasMany(Reply::class, 'enquiry_id')->orderBy('created_at', 'desc');
    }

    public function last_reply()
    {
        return $this->hasOne(Reply::class, 'enquiry_id')->latestOfMany();
    }

    public function unseen()
    {
        return $this->hasMany(Reply::class, 'enquiry_id')->unseen();
    }

    public function markedAsSeen()
    {
        $this->seen = true;
        $this->unseen()->update([
            'seen' => true
        ]);
        $this->save();
        return $this;
    }

    public function createReply(array $attributes = [])
    {
        $reply = new Reply($attributes);
        $reply->user()->associate(current_user());
        return $this->replies()->save($reply);
    }

    public function scopeWhereType($query, $type)
    {
        switch ($type) {
            case 'users':
                return $query->whereNotNull('subject');
                break;

            default:
                return $query->whereNull('subject');
                break;
        }
    }

    public function scopeOnlyOwner($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->where('id', current_user()->id);
        });
    }

    public function scopeOnlyUnread($query)
    {
        return $query->where('status', AppStatus::STAFF_REPLIED);
    }

    public function scopeOnlyActive($query)
    {
        return $query->onlyStatus('Live');
    }

    public function scopeOnlyStatus($query, $status = null)
    {
        switch ($status) {
            case 'Live':
                if (is_user()) {
                    return $query->whereUserArchived(0);
                } else {
                    return $query->whereIsArchived(0);
                }
                break;

            case 'Archive':
                if (is_user()) {
                    return $query->whereUserArchived(1);
                } else {
                    return $query->whereIsArchived(1);
                }
                break;
        }

        return $query;
    }

    public function scopeSortBy($query, $column = 'created_at', $direction = 'asc')
    {
        switch ($column) {
            case 'last_reply':
                return $query->select("enquiries.*")
                    ->leftJoin('replies', function ($join) {
                        $join->on('replies.enquiry_id', '=', "enquiries.id");
                    })
                    ->groupBy("enquiries.id")
                    ->orderBy(DB::raw('replies.created_at IS NULL'), 'desc')
                    ->orderBy(DB::raw('replies.created_at'), $direction ?? 'asc');
                break;

            default:
                return $query->orderBy($column ?: 'created_at', $direction ?? 'asc');
                break;
        }
    }

    public function renderNotification($type = null): Notification
    {
        $default = $this->source ? 'user:enquiry-confirmation' : 'user:enquiry-notification';

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
            '{{USER_NAME}}' => optional($this->user)->name ?? $this->name,
            '{{USER_ID}}' => optional($this->user)->id,
            '{{USER_FIRST_NAME}}' => optional($this->user)->first_name,
            '{{USER_LAST_NAME}}' => optional($this->user)->last_name,
            '{{USER_EMAIL}}' => optional($this->user)->email ?? $this->email,
            '{{USER_PHONE_NUMBER}}' => optional($this->user)->phone_number ?? $this->phone,
            '{{ENQUIRY_ID}}' => $this->id,
            '{{ENQUIRY_URL}}' => member_url("enquiries/{$this->id}?action=edit"),
            '{{ENQUIRY_ATTACHMENTS}}' => $attachments,
            '{{ENQUIRY_SUBJECT}}' => $this->subject,
            '{{ENQUIRY_MESSAGE}}' => $this->message,
        ];

        return $template->fill([
            'subject' => replace_short_code($template->subject, $shortCodes),
            'content' => replace_short_code($template->content, $shortCodes),
        ]);
    }

    public function sendPushNotify($type = null)
    {
        try {
            $default = $this->source ? 'push:enquiry-confirmation' : 'push:enquiry-notification';

            $template = $this->renderNotification($type ?? $default);

            SendPushNotification::dispatch($this->user, [
                'title' => $template->subject,
                'body' => html_text($template->content)
            ], [
                'route' => "/enquiries/{$this->id}?action=edit",
                'enquiry_id' => $this->id,
            ]);

            SendWhatsappNotification::dispatch($this->user, "{$template->subject}\n{$template->content}");
        } catch (\Exception $e) {
            //throw $e;
            report($e);
        }
    }

    protected static function booted()
    {
        parent::booted();
        static::creating(function ($model) {
            if (empty($model->status)) {
                $model->status = AppStatus::PENDING->value;
            }
            if (empty($model->email)) {
                $model->email = optional(current_user())->email;
            }
        });
    }
}
