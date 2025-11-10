<?php

namespace App\Modules\Planificacion\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'subject',
        'content',
        'status',
        'parent_id',
        'version',
        'internal_id',
        'category',
        'typification',
        'reference_number',
        'document_type_id',
        'on_behalf_of_user_id',
        'interaction_mode',
    ];

    protected $casts = [
        'content' => 'json',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function workflows()
    {
        return $this->hasMany(DocumentWorkflow::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}

