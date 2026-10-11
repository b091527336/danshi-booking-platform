<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('audience', 20);
            $table->string('fingerprint', 64);
            $table->string('retry_key', 36);
            $table->text('body');
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->timestamps();
            $table->unique(['booking_id', 'audience', 'fingerprint'], 'booking_notification_unique');
        });
        Schema::create('line_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('audience', 20);
            $table->string('recipient_key', 80)->unique();
            $table->string('channel', 50);
            $table->text('line_id');
            $table->timestamps();
        });
        Schema::create('line_pairing_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code_hash', 64)->unique();
            $table->string('channel', 50);
            $table->string('audience', 20);
            $table->json('organization_ids');
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_pairing_codes');
        Schema::dropIfExists('line_recipients');
        Schema::dropIfExists('booking_notifications');
    }
};
