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
        Schema::create('user_group_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_group_id')->nullable()->constrained();
            $table->string('module')->nullable();

            $table->boolean('list')->default(false);
            $table->boolean('create')->default(false);
            $table->boolean('edit')->default(false);
            $table->boolean('delete')->default(false);
            $table->boolean('details')->default(false);
            $table->boolean('only_associated')->default(false);
            $table->boolean('export')->default(false);
            $table->boolean('import')->default(false);
            $table->boolean('config')->default(false);

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
        Schema::dropIfExists('user_group_permissions');
    }
};
