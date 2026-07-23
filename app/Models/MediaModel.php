<?php

namespace App\Models;

use App\Entities\Media;
use CodeIgniter\Model;

class MediaModel extends Model
{
    protected $table          = 'media';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = Media::class;
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'uuid',
        'disk',
        'path',
        'cdn_url',
        'width',
        'height',
        'mime_type',
        'alt_text',
        'alt_text_source',
        'caption',
        'credit',
        'source',
        'generated_by',
        'ai_image_job_id',
        'uploaded_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'uuid'            => 'required|max_length[36]',
        'disk'            => 'required|max_length[50]',
        'path'            => 'required|max_length[500]',
        'mime_type'       => 'required|max_length[100]',
        'alt_text_source' => 'permit_empty|in_list[ai,manual]',
        'source'          => 'required|in_list[upload,ai_generated,stock]',
        'generated_by'    => 'required|in_list[human,ai,stock]',
    ];
}
