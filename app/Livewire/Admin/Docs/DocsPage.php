<?php

namespace App\Livewire\Admin\Docs;

use App\Livewire\Admin\Base\AdminPage;

/**
 * /admin/dev-docs/{topic?} — In-app developer documentation.
 *
 * Topics correspond to Blade partials under
 * resources/views/admin/docs/_partials/<topic>.blade.php.
 */
class DocsPage extends AdminPage
{
    public string $topic = 'index';

    public static function topics(): array
    {
        return [
            'index' => ['label' => 'Overview', 'group' => 'Start', 'icon' => '📚'],
            'architecture' => ['label' => 'Architecture & Stack', 'group' => 'Start', 'icon' => '🏗️'],
            'tenancy' => ['label' => 'Multi-Tenancy', 'group' => 'Start', 'icon' => '🏢'],

            'central-pages' => ['label' => 'Central Admin Pages', 'group' => 'Admin', 'icon' => '🛠'],
            'tenant-pages' => ['label' => 'Tenant Admin Pages', 'group' => 'Admin', 'icon' => '🏪'],
            'permissions' => ['label' => 'Permissions & Roles', 'group' => 'Admin', 'icon' => '🔑'],

            'finance' => ['label' => 'Finance & Calculations', 'group' => 'Finance', 'icon' => '💰'],
            'orders' => ['label' => 'Orders & Order Lifecycle', 'group' => 'Finance', 'icon' => '📦'],
            'settlements' => ['label' => 'Settlements & Payouts', 'group' => 'Finance', 'icon' => '🧾'],

            'website' => ['label' => 'Central Website', 'group' => 'Frontend', 'icon' => '🌐'],
            'themes' => ['label' => 'Themes & Storefront', 'group' => 'Frontend', 'icon' => '🎨'],

            'integrations' => ['label' => 'Integrations', 'group' => 'Platform', 'icon' => '🔌'],
            'mail' => ['label' => 'Email Templates & Mail', 'group' => 'Platform', 'icon' => '✉️'],
            'python' => ['label' => 'Python Modules', 'group' => 'Platform', 'icon' => '🐍'],

            'database' => ['label' => 'Database Schema', 'group' => 'Reference', 'icon' => '🗄'],
            'routes' => ['label' => 'Routes Reference', 'group' => 'Reference', 'icon' => '🛣'],
        ];
    }

    public function mount(string $topic = 'index'): void
    {
        $this->authorizePermission('system.dev-docs.view');
        $this->topic = array_key_exists($topic, self::topics()) ? $topic : 'index';
    }

    protected function pageMeta(): array
    {
        $meta = self::topics()[$this->topic] ?? ['label' => 'Docs'];
        return [
            'title' => 'Developer Documentation — ' . $meta['label'],
            'badge' => 'Internal · Dev Docs',
            'description' => 'Reference and design documentation for developers maintaining this platform.',
        ];
    }

    protected function pageView(): string
    {
        return 'livewire.admin.docs.docs-page';
    }

    protected function pageData(): array
    {
        return array_merge(parent::pageData(), [
            'topic' => $this->topic,
            'topics' => self::topics(),
            'partial' => 'admin.docs._partials.' . $this->topic,
        ]);
    }
}
