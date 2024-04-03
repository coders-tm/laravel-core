<?php

namespace Coderstm\Models;

use Coderstm\Traits\Core;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Casts\Attribute;

class File extends Model
{
    use Core;

    public static $route = 'files.download';

    protected $file;

    protected $fillable = [
        'disk',
        'url',
        'path',
        'original_file_name',
        'hash',
        'mime_type',
        'extension',
        'size',
        'ref',
    ];

    protected $appends = [
        'name',
        'is_image',
        'is_pdf',
        'icon',
    ];

    protected $casts = [
        'is_embed' => 'boolean',
    ];

    public function __construct(array $attributes = array())
    {
        parent::__construct($attributes);
        $this->attributes['disk'] = isset($attributes['disk']) ? $attributes['disk'] :  config('filesystems.default');
    }

    public function setHttpFile($file)
    {
        $this->file = $file;
        $this->original_file_name = $file->getClientOriginalName();
        $this->hash = md5_file($file->getRealPath());
        $this->mime_type = $file->getMimeType();
        $this->extension = $file->extension();
        $this->size = $file->getSize();
    }

    public function save($options = array())
    {
        if ($this->file) {
            $this->path = $this->file->storeAs('files', $this->hash . '.' . $this->extension, $this->disk);
            $this->url = route(static::$route, [
                'hash' => $this->hash,
                'path' => $this->original_file_name,
            ]);
        }
        return parent::save($options);
    }

    public function modify($options = array())
    {
        if ($this->file) {
            $this->path = $this->file->storeAs('public', $this->path);
        }
        return parent::update($options);
    }

    public function delete()
    {
        $count = self::wherePath($this->path)->count();
        if ($count == 1) {
            Storage::delete($this->path);
        }
        return parent::delete();
    }

    public function fileable()
    {
        return $this->morphTo();
    }

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? $value : route(static::$route, [
                'hash' => $this->hash,
                'path' => $this->original_file_name,
            ]),
        );
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->original_file_name,
        );
    }

    protected function isImage(): Attribute
    {
        return Attribute::make(
            get: fn () => Str::contains($this->mime_type, 'image') && !$this->is_embed,
        );
    }

    protected function isPdf(): Attribute
    {
        return Attribute::make(
            get: fn () => Str::contains($this->mime_type, 'pdf'),
        );
    }

    protected function icon(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->fileType($this->original_file_name),
        );
    }

    protected function fileType($file_name)
    {
        $extension = explode('.', $file_name);
        $extension = $extension[count($extension) - 1];
        switch ($extension) {
            case 'pdf':
                $type = 'pdf';
                break;
            case 'docx':
            case 'doc':
                $type = 'word';
                break;
            case 'xls':
            case 'xlsx':
                $type = 'excel';
                break;
            case 'mp3':
            case 'ogg':
            case 'wav':
                $type = 'audio';
                break;
            case 'mp4':
            case 'mov':
                $type = 'video';
                break;
            case 'zip':
            case '7z':
            case 'rar':
                $type = 'archive';
                break;
            case 'jpg':
            case 'jpeg':
            case 'png':
                $type = 'image';
                break;
            default:
                $type = 'alt';
        }
        return $type;
    }

    static public function findByHash(string $hash)
    {
        return static::where('hash', $hash)->firstOrFail();
    }
}
