<?php

namespace App\Livewire\Admin\Blog;

use App\Enums\ContentStatus;
use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\BlogPost;
use App\Repositories\BlogPostRepository;
use App\Services\BlogPostService;
use Livewire\WithPagination;

class BlogPostsList extends ListPage
{
    use InteractsWithAdminUi;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Blog Posts',
            'badge' => 'Content',
            'description' => 'Manage translated blog posts, category assignments, hero media, and publishing state.',
            'actionLabel' => 'Add Post',
            'tableTitle' => 'Central Blog Posts',
            'headers' => ['Title', 'Category', 'Published', 'Updated At', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $canManage = $this->hasPermission('content.blog-posts.manage');
        $repository = app(BlogPostRepository::class);
        $records = $repository->paginate([
            'search' => $this->search,
            'status' => $this->statusFilter,
        ]);
        $stats = $repository->stats();

        return array_merge(parent::pageData(), [
            'actionUrl' => $canManage ? route('admin.blog.posts.create') : null,
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Title or slug'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All statuses', 'published' => 'Published', 'draft' => 'Draft', 'archived' => 'Archived']],
            ],
            'statistics' => [
                ['label' => 'Posts', 'value' => number_format($stats['total']), 'caption' => 'All central blog posts', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Published', 'value' => number_format($stats['published']), 'caption' => 'Visible marketing content', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Drafts', 'value' => number_format($stats['drafts']), 'caption' => 'Still in editorial review', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(fn($post) => [
                '<div class="entity-title">' . e($post->title) . '</div><div class="entity-subtitle">/' . e($post->slug) . '</div>',
                e($post->category?->translationValue('name') ?? 'Uncategorized'),
                '<span class="badge ' . ($post->status === ContentStatus::Published ? 'badge-green' : ($post->status === ContentStatus::Archived ? 'badge-red' : 'badge-amber')) . '">' . e($post->status->label()) . '</span>',
                e($post->updated_at?->format('M d, Y')),
                $canManage
                ? '<div class="flex gap-2 flex-wrap"><a href="' . route('admin.blog.posts.edit', $post->id) . '"  class="btn btn-secondary btn-sm">Edit</a>' . ($post->status === ContentStatus::Published
                    ? '<button type="button" class="btn btn-secondary btn-sm" wire:click="archivePost(' . $post->id . ')">Archive</button>'
                    : '<button type="button" class="btn btn-secondary btn-sm" wire:click="publishPost(' . $post->id . ')">Publish</button>') . '<button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $post->id . ')">Delete</button></div>'
                : '<span class="entity-subtitle">View only</span>',
            ])->all(),
            'tableDescription' => $records->total() . ' blog posts matched the content filters.',
        ]);
    }

    public function publishPost(int $postId): void
    {
        $this->authorizePermission('content.blog-posts.manage');
        BlogPost::query()->findOrFail($postId)->update([
            'status' => ContentStatus::Published->value,
            'published_at' => now(),
        ]);
        $this->toast('Blog post published successfully.');
    }

    public function archivePost(int $postId): void
    {
        $this->authorizePermission('content.blog-posts.manage');
        BlogPost::query()->findOrFail($postId)->update([
            'status' => ContentStatus::Archived->value,
        ]);
        $this->toast('Blog post archived successfully.');
    }

    public function confirmDelete(int $postId): void
    {
        $this->authorizePermission('content.blog-posts.manage');
        $this->confirmAction('deletePost', [$postId], [
            'title' => 'Delete blog post?',
            'text' => 'This content entry will be permanently removed.',
            'confirmButtonText' => 'Delete post',
        ]);
    }

    public function deletePost(int $postId, BlogPostService $service): void
    {
        $this->authorizePermission('content.blog-posts.manage');
        $service->delete(BlogPost::query()->findOrFail($postId));
        $this->toast('Blog post deleted successfully.');
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }
}
