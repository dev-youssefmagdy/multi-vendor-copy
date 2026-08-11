<?php

namespace App\Livewire\Website;

use App\Enums\CategoryStatus;
use App\Enums\ContentStatus;
use App\Enums\PackageStatus;
use App\Enums\SubscriberStatus;
use App\Enums\TenantStatus;
use App\Models\BlogPost;
use App\Models\Catalog;
use App\Models\Category;
use App\Models\Faq;
use App\Models\NewsletterSubscriber;
use App\Models\Package;
use App\Models\Template;
use App\Models\Tenant;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class LandingPage extends Component
{
    public string $newsletterEmail = '';
    public bool $newsletterSent = false;
    public string $newsletterError = '';

    public function subscribeNewsletter(): void
    {
        $this->newsletterError = '';

        $key = 'newsletter:' . request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->newsletterError = "Too many attempts. Please try again in {$seconds} seconds.";
            return;
        }

        RateLimiter::hit($key, 60);

        $this->validate(
            ['newsletterEmail' => 'required|email|max:255'],
            [
                'newsletterEmail.required' => 'Please enter your email address.',
                'newsletterEmail.email' => 'Please enter a valid email address.'
            ],
        );

        $existing = NewsletterSubscriber::query()
            ->where('email', $this->newsletterEmail)
            ->first();

        if ($existing) {
            if ($existing->status === SubscriberStatus::Unsubscribed) {
                $existing->update([
                    'status' => SubscriberStatus::Subscribed,
                    'subscribed_at' => now(),
                    'unsubscribed_at' => null,
                ]);
            }
            // Already subscribed — silently succeed (no user-facing error)
        } else {
            NewsletterSubscriber::create([
                'email' => $this->newsletterEmail,
                'status' => SubscriberStatus::Subscribed,
                'ip_address' => request()->ip(),
                'source' => 'landing',
                'subscribed_at' => now(),
            ]);
        }

        $this->newsletterSent = true;
        $this->newsletterEmail = '';
    }
    public function render()
    {
        $packages = Package::query()
            ->with('translations.language')
            ->where('status', PackageStatus::Published->value)

            ->get();

        $latestPosts = BlogPost::query()
            ->with(['translations.language', 'category.translations.language'])
            ->where('status', ContentStatus::Published->value)
            ->whereNotNull('published_at')
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        $templates = Template::query()
            ->with('previewFile')
            ->where('is_active', true)
            ->get();

        $categories = Category::query()
            ->with('files')
            ->where('status', CategoryStatus::Published->value)
            ->whereNull('parent_id')

            ->limit(8)
            ->get();

        $faqs = Faq::query()
            ->with('translations.language')
            ->where('status', ContentStatus::Active->value)
            ->limit(6)
            ->get();

        $stats = [
            [
                'value' => '100%',
                'label' => 'Security Ensured',
                'description' => 'Protected & Reliable.',
            ],
            [
                'value' => number_format(Catalog::query()->where('status', 'active')->count()) . '+',
                'label' => 'Original Products',
                'description' => 'Ready for your store.',
            ],
            [
                'value' => number_format(Tenant::query()->where('status', TenantStatus::Active->value)->count()) . '+',
                'label' => 'Daily Transactions',
                'description' => 'Processed through our platform.',
            ],
            [
                'value' => number_format(BlogPost::query()->where('status', ContentStatus::Published->value)->count()) . '+',
                'label' => 'Active Users',
                'description' => 'Users trust our templates daily.',
            ],
        ];

        return view('livewire.website.landing', [
            'packages' => $packages,
            'latestPosts' => $latestPosts,
            'templates' => $templates,
            'categories' => $categories,
            'faqs' => $faqs,
            'stats' => $stats,
        ])->layout('layouts.website', ['title' => 'Ecommet — Launch Your Online Store Today']);
    }
}
