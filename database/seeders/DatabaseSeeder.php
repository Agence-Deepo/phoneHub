<?php

namespace Database\Seeders;

use App\Models\Automation;
use App\Models\Phone;
use App\Models\PhoneApp;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()
            ->whereIn('email', ['admin@phonehub.local', 'admin@geelark.local'])
            ->first();

        if ($admin) {
            $admin->update([
                'name' => 'Admin',
                'email' => 'admin@phonehub.local',
                'password' => 'password',
                'email_verified_at' => now(),
            ]);
        } else {
            User::query()->create([
                'name' => 'Admin',
                'email' => 'admin@phonehub.local',
                'password' => 'password',
                'email_verified_at' => now(),
            ]);
        }

        if (Phone::exists()) {
            return;
        }

        $phones = [
            [
                'geelark_id' => 'demo_fr_tiktok_01',
                'name' => 'TikTok FR-01',
                'serial_no' => '1001',
                'status' => 'online',
                'country' => 'France',
                'group_name' => 'Marketing',
                'tags' => ['tiktok', 'fr', 'warmup'],
                'proxy' => 'socks5://user:***@proxy.fr:1080',
                'mobile_type' => 'Android 13',
                'remote_url' => 'https://example.com/demo-phone/fr01',
                'remark' => 'Compte principal warmup',
                'equipment_info' => ['deviceBrand' => 'samsung', 'deviceModel' => 'Galaxy S23'],
                'last_synced_at' => now(),
            ],
            [
                'geelark_id' => 'demo_us_ig_02',
                'name' => 'Instagram US-02',
                'serial_no' => '1002',
                'status' => 'offline',
                'country' => 'United States',
                'group_name' => 'Growth',
                'tags' => ['instagram', 'us'],
                'proxy' => 'http://user:***@proxy.us:8080',
                'mobile_type' => 'Android 14',
                'remark' => 'En pause',
                'last_synced_at' => now()->subHour(),
            ],
            [
                'geelark_id' => 'demo_sg_fb_03',
                'name' => 'Facebook SG-03',
                'serial_no' => '1003',
                'status' => 'online',
                'country' => 'Singapore',
                'group_name' => 'SEA',
                'tags' => ['facebook', 'sg'],
                'proxy' => null,
                'mobile_type' => 'Android 12',
                'remote_url' => 'https://example.com/demo-phone/sg03',
                'last_synced_at' => now(),
            ],
            [
                'geelark_id' => 'demo_de_tt_04',
                'name' => 'TikTok DE-04',
                'serial_no' => '1004',
                'status' => 'starting',
                'country' => 'Germany',
                'group_name' => 'Marketing',
                'tags' => ['tiktok', 'de'],
                'proxy' => 'socks5://de.proxy:30000',
                'mobile_type' => 'Android 15',
                'last_synced_at' => now(),
            ],
            [
                'geelark_id' => 'demo_br_ig_05',
                'name' => 'Instagram BR-05',
                'serial_no' => '1005',
                'status' => 'error',
                'country' => 'Brazil',
                'group_name' => 'LATAM',
                'tags' => ['instagram', 'br'],
                'proxy' => 'socks5://br.proxy:1080',
                'mobile_type' => 'Android 13',
                'remark' => 'Proxy KO',
                'last_synced_at' => now()->subDay(),
            ],
        ];

        foreach ($phones as $data) {
            $phone = Phone::create($data);

            if ($phone->status === 'online') {
                PhoneApp::create([
                    'phone_id' => $phone->id,
                    'app_name' => str_contains(strtolower($phone->name), 'tiktok') ? 'TikTok' : 'Instagram',
                    'package_name' => str_contains(strtolower($phone->name), 'tiktok')
                        ? 'com.zhiliaoapp.musically'
                        : 'com.instagram.android',
                    'app_version_id' => 'demo_version_1',
                    'status' => 'installed',
                ]);
            }
        }

        Automation::create([
            'phone_id' => Phone::where('name', 'TikTok FR-01')->value('id'),
            'type' => 'warmup',
            'status' => 'success',
            'geelark_task_id' => 'demo_task_1',
            'config' => ['duration' => 15],
            'message' => 'Warmup terminé',
        ]);

        Automation::create([
            'phone_id' => Phone::where('name', 'Facebook SG-03')->value('id'),
            'type' => 'custom',
            'status' => 'running',
            'geelark_task_id' => 'demo_task_2',
            'message' => 'En cours d’exécution',
        ]);
    }
}
