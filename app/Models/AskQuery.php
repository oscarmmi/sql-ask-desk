<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AskQuery extends Model
{
    protected $fillable = [
        'question',
        'sql_text',
        'row_count',
        'status',
        'preview_json',
    ];

    protected function casts(): array
    {
        return [
            'preview_json' => 'array',
        ];
    }
}
