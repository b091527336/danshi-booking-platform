<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('external_provider')->default('tablesit');
            $table->string('external_id')->nullable()->index();
            $table->string('timezone')->default('Asia/Taipei');
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['external_provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
