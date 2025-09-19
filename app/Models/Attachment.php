<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'original_name',
        'storage_path',
        'mime_type',
        'size_in_bytes',
    ];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }
}
