<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
     use HasFactory, SoftDeletes;
    
    // Kolom yang boleh diisi
    protected $fillable = [
        'course_id', 
        'title', 
        'description', 
        'deadline'
    ];
    public function course(): BelongsTo
{
    return $this->belongsTo(Course::class);
}

public function submissions(): HasMany
{
    return $this->hasMany(Submission::class);
}
}
