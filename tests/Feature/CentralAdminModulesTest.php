<?php

namespace Tests\Feature;

use App\Enums\ActivationStatus;
use App\Enums\ContentStatus;
use App\Enums\DomainRequestStatus;
use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\DomainRequest;
use App\Models\StaticPage;
use App\Services\AdminRoleService;
use App\Services\AdminUserService;
use App\Services\BlogPostService;
use App\Services\DomainRequestService;
use App\Services\StaticPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentralAdminModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_services_manage_blog_posts_static_pages_and_domain_requests(): void
    {
        $category = BlogCategory::query()->create([
            'name' => 'Announcements',
            'slug' => 'announcements',
            'status' => ContentStatus::Active,
            'sort_order' => 1,
        ]);

        $post = app(BlogPostService::class)->save([
            'blog_category_id' => $category->id,
            'title' => 'Platform Launch',
            'slug' => 'platform-launch',
            'excerpt' => 'Launch summary',
            'content' => 'Launch details',
            'status' => ContentStatus::Published->value,
            'sort_order' => 3,
        ]);

        $page = app(StaticPageService::class)->save([
            'title' => 'About Us',
            'slug' => 'about-us',
            'content' => 'About content',
            'status' => ContentStatus::Active->value,
            'sort_order' => 2,
        ]);

        $request = app(DomainRequestService::class)->save([
            'domain' => 'merchant.example.com',
            'status' => DomainRequestStatus::Pending->value,
        ]);

        app(DomainRequestService::class)->markStatus($request, DomainRequestStatus::Connected);

        $this->assertDatabaseHas('blog_posts', [
            'id' => $post->id,
            'title' => 'Platform Launch',
            'status' => ContentStatus::Published->value,
        ]);
        $this->assertNotNull($post->fresh()->published_at);

        $this->assertDatabaseHas('static_pages', [
            'id' => $page->id,
            'slug' => 'about-us',
            'status' => ContentStatus::Active->value,
        ]);

        $this->assertDatabaseHas('domain_requests', [
            'id' => $request->id,
            'domain' => 'merchant.example.com',
            'status' => DomainRequestStatus::Connected->value,
        ]);
        $this->assertNotNull($request->fresh()->verified_at);
    }

    public function test_access_services_manage_roles_permissions_and_admin_assignments(): void
    {
        $role = app(AdminRoleService::class)->save([
            'name' => 'Operations Manager',
            'permissions' => ['content.blog.manage', 'domains.requests.review'],
        ]);

        $admin = app(AdminUserService::class)->save([
            'role_id' => $role->id,
            'name' => 'Ops Lead',
            'email' => 'ops@example.com',
            'status' => ActivationStatus::Active->value,
        ]);

        $this->assertDatabaseHas('admin_roles', [
            'id' => $role->id,
            'name' => 'Operations Manager',
            'permissions_count' => 2,
        ]);
        $this->assertSame(['content.blog.manage', 'domains.requests.review'], $role->fresh()->permissions);

        $this->assertDatabaseHas('admins', [
            'id' => $admin->id,
            'role_id' => $role->id,
            'email' => 'ops@example.com',
            'status' => ActivationStatus::Active->value,
        ]);

        app(AdminRoleService::class)->delete($role->fresh());

        $this->assertNull($admin->fresh()->role_id);
        $this->assertDatabaseMissing('admin_roles', ['id' => $role->id]);
    }

    public function test_central_admin_routes_render_completed_records(): void
    {
        $category = BlogCategory::query()->create([
            'name' => 'News',
            'slug' => 'news',
            'status' => ContentStatus::Active,
        ]);

        BlogPost::query()->create([
            'blog_category_id' => $category->id,
            'title' => 'Roadmap Update',
            'slug' => 'roadmap-update',
            'status' => ContentStatus::Published,
            'published_at' => now(),
        ]);

        StaticPage::query()->create([
            'title' => 'Terms',
            'slug' => 'terms',
            'content' => 'Terms content',
            'status' => ContentStatus::Active,
        ]);

        DomainRequest::query()->create([
            'domain' => 'custom.example.com',
            'status' => DomainRequestStatus::Pending,
            'requested_at' => now(),
        ]);

        $role = AdminRole::query()->create([
            'name' => 'Support',
            'permissions' => ['content.faqs.manage'],
            'permissions_count' => 1,
        ]);

        AdminUser::query()->create([
            'role_id' => $role->id,
            'name' => 'Support Lead',
            'email' => 'support@example.com',
            'status' => ActivationStatus::Active,
        ]);

        $this->get('http://localhost/admin/blog/posts')
            ->assertOk()
            ->assertSee('Roadmap Update')
            ->assertSee('News');

        $this->get('http://localhost/admin/domains/requests')
            ->assertOk()
            ->assertSee('custom.example.com');

        $this->get('http://localhost/admin/pages')
            ->assertOk()
            ->assertSee('Terms');

        $this->get('http://localhost/admin/admins')
            ->assertOk()
            ->assertSee('Support Lead')
            ->assertSee('support@example.com');

        $this->get('http://localhost/admin/admins/roles-permissions')
            ->assertOk()
            ->assertSee('Support')
            ->assertSee('1 permissions');
    }
}
