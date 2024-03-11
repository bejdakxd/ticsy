<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_personalizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('table_name');
            $table->text('columns');
            $table->unique(['user_id', 'table_name']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_personalizations');
    }
};