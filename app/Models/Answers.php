<?php namespace App\Models;

use CodeIgniter\Model;

class Answers extends Model
{
    protected $table      = 'answers';
    protected $primaryKey = 'id';

    protected $returnType     = 'App\Entities\answer';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'id',
        'question_id',
        'user_id',
        'answer',
        'created_at',
        'created_by',
        'modified_at',
        'modified_by',
        'deleted',
        'deleted_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'modified_at';
    protected $deletedField  = 'deleted_at';
    protected $validationMessages = [];
    protected $skipValidation     = false;
    protected $validationRules    = [
        'user_id' => 'required',
        'user_id' => 'required',
        'answer' => 'required',
    ];
}
