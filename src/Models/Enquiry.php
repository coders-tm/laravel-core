<?php

namespace Coderstm\Models;

use Coderstm\Coderstm;
use Coderstm\Database\Factories\EnquiryFactory;
use Coderstm\Enum\AppStatus;
use Coderstm\Events\EnquiryCreated;
use Coderstm\Models\Enquiry\Reply;
use Coderstm\Traits\Core;
use Coderstm\Traits\Fileable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Enquiry extends Model
{
    use Core, Fileable;

    protected $dispatchesEvents = ['created' => EnquiryCreated::class];

    protected $fillable = ['name', 'email', 'phone', 'subject', 'message', 'status', 'seen', 'is_archived', 'user_archived', 'source'];

    protected $with = ['last_reply.user', 'user'];

    protected $appends = ['has_unseen'];

    protected $withCount = ['unseen'];

    protected $casts = ['status' => AppStatus::class, 'seen' => 'boolean', 'is_archived' => 'boolean', 'user_archived' => 'boolean', 'source' => 'boolean'];

    public function getHasUnseenAttribute()
    {
        return $this->unseen_count > 0 || ! $this->seen;
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
        return $this->hasOne(Reply::class, 'enquiry_id')->orderBy('created_at', 'desc');
    }

    public function unseen()
    {
        return $this->hasMany(Reply::class, 'enquiry_id')->unseen();
    }

    public function markedAsSeen()
    {
        $this->seen = true;
        $this->unseen()->update(['seen' => true]);
        $this->save();

        return $this;
    }

    public function createReply(array $attributes = [])
    {
        $reply = new Reply($attributes);
        $reply->user()->associate(user());

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
            $q->where('id', user()->id);
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
                if (Auth::guard('users')->hasUser()) {
                    return $query->whereUserArchived(0);
                } else {
                    return $query->whereIsArchived(0);
                }
                break;
            case 'Archive':
                if (Auth::guard('users')->hasUser()) {
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
                return $query->select('enquiries.*')->leftJoin('replies', function ($join) {
                    $join->on('replies.enquiry_id', '=', 'enquiries.id');
                })->groupBy('enquiries.id')->orderBy(DB::raw('replies.created_at IS NULL'), 'desc')->orderBy(DB::raw('replies.created_at'), $direction ?? 'asc');
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
        $data = ['user' => $this->user?->getShortCodes() ?? ['name' => $this->name, 'email' => $this->email, 'phone' => $this->phone], 'enquiry' => $this->getShortCodes()];
        $rendered = $template->render($data);

        return $template->fill(['subject' => $rendered['subject'], 'content' => $rendered['content']]);
    }

    public function renderPushNotification($type = null)
    {
        $default = $this->source ? 'push:enquiry-confirmation' : 'push:enquiry-notification';
        $template = $this->renderNotification($type ?? $default);

        return optional((object) ['subject' => $template->subject, 'content' => html_text($template->content), 'whatsappContent' => html_text("{$template->subject}\n{$template->content}"), 'data' => ['route' => user_route("/enquiries/{$this->id}?action=edit"), 'enquiry_id' => (string) $this->id]]);
    }

    public function getShortCodes(): array
    {
        return ['id' => $this->id, 'url' => app_url("enquiries/{$this->id}?action=edit"), 'admin_url' => admin_url("enquiries/{$this->id}?action=edit"), 'attachments' => $this->media->map(function ($file) {
            return ['name' => $file->name, 'url' => $file->url];
        })->toArray(), 'subject' => $this->subject, 'status' => $this->status->value, 'message' => $this->message];
    }

    protected static function newFactory()
    {
        return EnquiryFactory::new();
    }

    protected static function booted()
    {
        parent::booted();
        static::creating(function ($model) {
            if (empty($model->status)) {
                $model->status = AppStatus::PENDING->value;
            }
            if (empty($model->email)) {
                $model->email = optional(user())->email;
            }
        });
    }
}
