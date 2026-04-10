<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    protected $fillable = ['name'];

    protected $casts = [
        'id' => 'integer',
    ];

    public function userDocuments()
    {
        return $this->hasMany(UserDocument::class);
    }
}
