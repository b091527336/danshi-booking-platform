<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_provider')->default('tablesit');
            $table->string('external_id');
            $table->string('status')->default('confirmed')->index();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('party_size')->nullable();
            $table->string('service_name')->nullable();
            $table->text('notes')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['external_provider', 'external_id']);
            $table->index(['organization_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
