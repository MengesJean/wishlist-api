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
        Schema::create('event_invites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('invited_email')->index();
            $table->foreignId('invited_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token_hash')->nullable()->unique();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->string('status')->default('pending');

            $table->dateTime('expires_at')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->dateTime('revoked_at')->nullable();

            $table->timestamps();

            $table->unique(['event_id', 'invited_email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_invites');
    }
};
