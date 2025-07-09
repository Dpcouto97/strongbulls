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
        Schema::create('user_groups', function (Blueprint $table) {
            $table->id();
            $table->text('name')->nullable();
            $table->boolean('status')->default(false);
            $table->softDeletes();
            $table->timestamps();
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
        Schema::dropIfExists('user_groups');
    }
};
