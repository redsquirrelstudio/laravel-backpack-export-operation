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
        Schema::create('export_log', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->index('export_log_user_id')->nullable();
            $table->text('file_path')->nullable();
            $table->string('file_type')->default('csv');
            $table->string('disk')->default('local');
            $table->text('model');
            $table->longText('config')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('export_log');
    }
};
