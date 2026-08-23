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
        Schema::create('ntfy_configurations', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable');
            $table->string('server_url');
            $table->string('topic');
            // Store auth token, username, and password as text to allow for encryption and accommodate longer values
            $table->text('auth_token')->nullable();
            $table->text('username')->nullable();
            $table->text('password')->nullable();
            $table->unique(['notifiable_type', 'notifiable_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ntfy_configurations');
    }
};
