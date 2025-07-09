<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AppointmentExercise extends Model
{
    use SoftDeletes;

    protected $table = 'appointment_exercise';

    protected $fillable = [
        'appointment_id', 'exercise_id', 'series', 'reps', 'weight', 'interval', 'order', 'created_by', 'updated_by', 'deleted_by'
    ];

    //Agendamento ( Belongs to one appointment )
    public function appointment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'plan_id');
    }

    //Exercicio ( Belongs to one exercise )
    public function exercise(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Exercise::class, 'exercise_id');
    }

}
