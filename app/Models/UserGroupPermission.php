<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserGroupPermission extends Model
{
    use SoftDeletes;

    protected $table = 'user_group_permissions';
    protected $fillable = [
        'user_group_id', 'module', 'list', 'create', 'edit', 'delete', 'details', 'only_associated', 'export', 'import', 'config', 'created_by', 'updated_by', 'deleted_by'
    ];
    protected $casts = [
        'list' => 'boolean',
        'create' => 'boolean',
        'edit' => 'boolean',
        'delete' => 'boolean',
        'details' => 'boolean',
        'only_associated' => 'boolean',
        'export' => 'boolean',
        'import' => 'boolean',
        'config' => 'boolean',
    ];

    public function user_group()
    {
        return $this->belongsTo(UserGroup::class, 'user_group_id');
    }
}
