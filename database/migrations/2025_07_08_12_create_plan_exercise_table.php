<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plan_exercise', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->nullable()->constrained();
            $table->foreignId('exercise_id')->nullable()->constrained();
            $table->integer('series')->nullable();
            $table->integer('reps')->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->integer('interval')->nullable();
            $table->integer('order')->nullable();
            $table->timestamps();
            $table->softDeletes();  // Adiciona a coluna deleted_at através do softDeletes
            $table->unsignedBigInteger('created_by')->nullable(); // Quem criou
            $table->unsignedBigInteger('updated_by')->nullable(); // Quem atualizou
            $table->unsignedBigInteger('deleted_by')->nullable(); //Para guardar quem eliminou
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_exercise');
    }
};
