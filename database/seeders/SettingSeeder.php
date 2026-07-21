<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'AarvixCMS', 'group' => 'general', 'type' => 'string'],
            ['key' => 'site_description', 'value' => 'A lightweight native Laravel CMS.', 'group' => 'general', 'type' => 'string'],
            ['key' => 'maintenance_mode', 'value' => 'false', 'group' => 'system', 'type' => 'boolean'],
            ['key' => 'posts_per_page', 'value' => '10', 'group' => 'reading', 'type' => 'integer'],
        ];

        foreach ($settings as $setting) {
            \App\Models\Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
