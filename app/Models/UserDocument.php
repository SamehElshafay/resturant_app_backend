<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDocument extends Model
{
    protected $fillable = ['user_id', 'document_type_id', 'file_path'];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'document_type_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }
}
