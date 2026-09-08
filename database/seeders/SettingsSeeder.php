<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'app_name', 'value' => 'Multi Vendor Central', 'type' => 'string', 'is_public' => false],
            ['key' => 'marketplace_name', 'value' => 'Marketplace HQ', 'type' => 'string', 'is_public' => true],
            ['key' => 'default_currency', 'value' => 'USD', 'type' => 'string', 'is_public' => true],
            ['key' => 'default_language', 'value' => 'en', 'type' => 'string', 'is_public' => true],
            ['key' => 'support_email', 'value' => 'support@example.com', 'type' => 'string', 'is_public' => true],
            ['key' => 'mail_mailer', 'value' => 'smtp', 'type' => 'string', 'is_public' => false],
            ['key' => 'mail_host', 'value' => 'smtp.example.com', 'type' => 'string', 'is_public' => false],
            ['key' => 'mail_port', 'value' => '587', 'type' => 'string', 'is_public' => false],
            ['key' => 'mail_username', 'value' => 'mailer@example.com', 'type' => 'string', 'is_public' => false],
            ['key' => 'mail_password', 'value' => 'secret-demo', 'type' => 'secret', 'is_public' => false],
            ['key' => 'mail_encryption', 'value' => 'tls', 'type' => 'string', 'is_public' => false],
            ['key' => 'mail_from_address', 'value' => 'noreply@example.com', 'type' => 'string', 'is_public' => false],
            ['key' => 'mail_from_name', 'value' => 'Marketplace HQ', 'type' => 'string', 'is_public' => false],
            ['key' => 'mail_queue', 'value' => '1', 'type' => 'boolean', 'is_public' => false],
            ['key' => 'header_logo', 'value' => asset('central-website/assets/image/4ad6eed1943b13bf773152f09ecf0d9ac498c5d3.png'), 'type' => 'string', 'is_public' => true],
            ['key' => 'footer_logo', 'value' => asset('central-website/assets/image/central-footer-logo.png'), 'type' => 'string', 'is_public' => true],
            ['key' => 'favicon', 'value' => '', 'type' => 'string', 'is_public' => true],
            ['key' => 'facebook_url', 'value' => '#', 'type' => 'string', 'is_public' => true],
            ['key' => 'twitter_url', 'value' => '#', 'type' => 'string', 'is_public' => true],
            ['key' => 'instagram_url', 'value' => '#', 'type' => 'string', 'is_public' => true],
            ['key' => 'youtube_url', 'value' => '#', 'type' => 'string', 'is_public' => true],
            ['key' => 'linkedin_url', 'value' => '#', 'type' => 'string', 'is_public' => true],
        ];

        foreach ($settings as $setting) {
            $this->seedAppSetting($setting);
        }
    }

    protected function seedAppSetting(array $setting): AppSetting
    {
        return AppSetting::query()->updateOrCreate(['key' => $setting['key']], $setting);
    }
}
