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
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained();      // Cliente
            $table->date('date')->nullable();                               // Data
            $table->integer('height')->nullable();                          // Altura
            $table->integer('bmr')->nullable();                             // Numero minimo de calorias necessarias
            $table->integer('visceral_fat')->nullable();                    // Gordura Visceral
            $table->decimal('weight', 8, 2)->nullable();        // PESO
            $table->decimal('imc', 8, 2)->nullable();           // IMC / BMI
            $table->decimal('muscle_mass', 8, 2)->nullable();   // Massa Muscular
            $table->decimal('bone_mass', 8, 2)->nullable();     // Massa Ossea
            $table->decimal('body_fat', 8, 2)->nullable();      // Gordura Corporal
            $table->decimal('body_water', 8, 2)->nullable();    // Agua no Corpo
            $table->text('description')->nullable();
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
        Schema::dropIfExists('evaluations');
    }
};
