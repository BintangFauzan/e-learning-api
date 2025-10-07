<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = ['name', 'description', 'lecturer_id'];

    public function students(): BelongsToMany
    {
        // Mahasiswa yang terdaftar di Mata Kuliah ini
        return $this->belongsToMany(User::class);
    }

    public function lecturer(): BelongsTo
    {
        // Dosen pengampu mata kuliah ini
        return $this->belongsTo(User::class, 'lecturer_id');
    }

     public function materials(): HasMany // Tambahan
    {
        // Satu Mata Kuliah punya banyak Materi
        return $this->hasMany(Material::class);
    }

     public function assignments(): HasMany // Tambahan
    {
        // Satu Mata Kuliah punya banyak Tugas
        return $this->hasMany(Assignment::class);
    }

    public function discussions(): HasMany // Tambahan
    {
        // Satu Mata Kuliah punya banyak Thread Diskusi
        return $this->hasMany(Discussion::class);
    }
}
