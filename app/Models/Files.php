<?php namespace App\Models;

use CodeIgniter\Model;

class Files extends Model
{
    protected $table      = 'files';
    protected $primaryKey = 'id';

    protected $returnType     = 'App\Entities\file';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'id',
        'file_name',
        'file_type',
        'file_ext',
        'file_size',
        'folder',
        'modified_by',
        'created_by',
        'created_at',
        'modified_at',
        'deleted_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'modified_at';
    protected $deletedField  = 'deleted_at';
    protected $validationMessages = [];
    protected $skipValidation     = false;
    protected $validationRules    = [];
}
