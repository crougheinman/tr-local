<?php namespace App\Models;

use CodeIgniter\Model;

class Announcements extends Model
{
    protected $table      = 'announcements';
    protected $primaryKey = 'id';

    protected $returnType     = 'App\Entities\announcement';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'id',
        'title',
        'body',
        'slug',
        'date_start',
        'date_end',
        'type',
        'banner',
        'created_at',
        'created_by',
        'modified_at',
        'modified_by',
        'deleted_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'modified_at';
    protected $deletedField  = 'deleted_at';
    protected $validationMessages = [];
    protected $skipValidation     = false;
    protected $validationRules    = [
        'title' => 'required',
        'body' => 'required',
        'date_start' => 'required',
        'type' => 'required',
    ];
}
