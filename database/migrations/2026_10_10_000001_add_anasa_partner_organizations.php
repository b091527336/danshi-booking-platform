<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();

        $organizations = [
            [
                'name' => 'ANASA 安耐曬｜總公司',
                'slug' => 'anasa-headquarters',
                'external_id' => '0a7b42ab-fdba-4823-9f97-b913e9ccf728',
            ],
            [
                'name' => 'ANASA 安耐曬｜台北辦公室',
                'slug' => 'anasa-taipei',
                'external_id' => 'de326113-a51d-4016-b997-97ef5de8cf3a',
            ],
            [
                'name' => 'ANASA 安耐曬｜桃園辦公室',
                'slug' => 'anasa-taoyuan',
                'external_id' => '9f5fee0b-371d-4ef8-bea7-3b618f2c5183',
            ],
            [
                'name' => 'ANASA 安耐曬｜台中辦公室',
                'slug' => 'anasa-taichung',
                'external_id' => '3dc669dd-7264-461e-aaa6-4a28bbb5262e',
            ],
            [
                'name' => 'ANASA 安耐曬｜台南辦公室',
                'slug' => 'anasa-tainan',
                'external_id' => 'd8095995-14ce-4c8e-ad3d-e64b0da8a937',
            ],
        ];

        foreach ($organizations as $organization) {
            DB::table('organizations')->updateOrInsert(
                [
                    'external_provider' => 'tablesit',
                    'external_id' => $organization['external_id'],
                ],
                [
                    'name' => $organization['name'],
                    'slug' => $organization['slug'],
                    'timezone' => 'Asia/Taipei',
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        // Keep live organization data intact if the deployment is rolled back.
    }
};
