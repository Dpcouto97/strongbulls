<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlanExercise extends Model
{
    use SoftDeletes;

    protected $table = 'plan_exercise';

    protected $fillable = [
        'plan_id', 'exercise_id', 'series', 'reps', 'weight', 'interval', 'order', 'created_by', 'updated_by', 'deleted_by'
    ];

    //Plano ( Belongs to one plan )
    public function plan(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    //Exercicio ( Belongs to one exercise )
    public function exercise(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Exercise::class, 'exercise_id');
    }
}
