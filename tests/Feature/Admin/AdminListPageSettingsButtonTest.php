<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\ActivationStatus;
use App\Livewire\Admin\Affiliate\PayoutsListPage;
use App\Livewire\Admin\Domain\DomainRequestsList;
use App\Models\AdminRole;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Client report: /admin/domains/requests crashed with MethodNotFoundException
 * [openSettingsModal]. The shared list-page view showed the affiliate "Program Settings"
 * button on every admin list page; only the affiliate pages have that modal.
 */
class AdminListPageSettingsButtonTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $role = AdminRole::query()->create([
            'name' => 'Role '.uniqid(), 'permissions' => ['affiliates.manage', 'domains.manage'], 'permissions_count' => 2,
        ]);

        $this->actingAs(AdminUser::query()->create([
            'role_id' => $role->id, 'name' => 'Admin', 'email' => uniqid('admin').'@example.com',
            'password' => Hash::make('password12345'), 'status' => ActivationStatus::Active,
        ]), 'admin');
    }

    public function test_non_affiliate_list_pages_have_no_program_settings_button(): void
    {
        $this->actingAsAdmin();

        Livewire::test(DomainRequestsList::class)
            ->assertOk()
            ->assertDontSee('openSettingsModal', false)
            ->assertDontSee('Program Settings');
    }

    public function test_affiliate_pages_keep_their_program_settings_button(): void
    {
        $this->actingAsAdmin();

        Livewire::test(PayoutsListPage::class)
            ->assertOk()
            ->assertSee('Program Settings')
            ->call('openSettingsModal')
            ->assertOk();
    }
}
