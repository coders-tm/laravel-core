<?php

namespace Coderstm\Models\Page;

use Coderstm\Traits\Logable;
use Coderstm\Traits\SerializeDate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    use Logable, HasFactory, SerializeDate;

    protected $table = 'page_templates';
    protected $dates = ['created_at', 'updated_at'];

    protected $fillable = [
        'name',
        'thumbnail',
        'data',
    ];

    protected $casts = [
        'data' => 'json',
    ];
}
