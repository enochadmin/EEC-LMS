<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_module_id',
        'title',
        'content',
        'video_path', // For MP4 files
        'attachment_path',
        'pdf_file_path',
        'youtube_link',
        'youtube_links',
        'order',
        'estimated_minutes',
    ];

    public function module()
    {
        return $this->belongsTo(TrainingModule::class, 'training_module_id');
    }
}

