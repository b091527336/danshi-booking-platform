<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('role')->default('admin')->after('password');
            $table->string('activation_token_hash')->nullable()->after('role');
            $table->timestamp('activation_expires_at')->nullable()->after('activation_token_hash');
        });

        Schema::create('organization_user', function (Blueprint $table) {
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['organization_id', 'user_id']);
        });

        $userId = DB::table('users')->insertGetId([
            'name' => 'ANASA 安耐曬管理者',
            'username' => 'anasa29483011',
            'email' => 'anasa29483011@client.dbp.local',
            'password' => Hash::make(Str::random(64)),
            'role' => 'client',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $organizationIds = DB::table('organizations')
            ->whereIn('external_id', [
                '80ed4091-3700-42b0-958f-cdede4917d74',
                '0a7b42ab-fdba-4823-9f97-b913e9ccf728',
                'de326113-a51d-4016-b997-97ef5de8cf3a',
                '9f5fee0b-371d-4ef8-bea7-3b618f2c5183',
                '3dc669dd-7264-461e-aaa6-4a28bbb5262e',
                'd8095995-14ce-4c8e-ad3d-e64b0da8a937',
            ])
            ->pluck('id');

        foreach ($organizationIds as $organizationId) {
            DB::table('organization_user')->insert([
                'organization_id' => $organizationId,
                'user_id' => $userId,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_user');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role', 'activation_token_hash', 'activation_expires_at']);
        });
    }
};
