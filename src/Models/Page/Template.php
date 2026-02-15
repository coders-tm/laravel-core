<?php

namespace Coderstm\Models\Page;

use Coderstm\Traits\Logable;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use HasFactory, Logable, SerializeDate;

    protected $table = 'page_templates';

    protected $logIgnore = ['data'];

    protected $fillable = ['name', 'thumbnail', 'data'];

    protected $casts = ['data' => 'json'];
}
