<?php

namespace App\Livewire\Tenant\Storefront\Layout;

use App\Repositories\Tenant\StorefrontRepository;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class LocaleSwitcher extends Component
{
    public bool $open = false;
    public string $tab = 'currency'; // 'currency' | 'language'

    public function openModal(string $tab = 'currency'): void
    {
        $this->tab = $tab;
        $this->open = true;
    }

    #[On('open-locale-modal')]
    public function openFromEvent(string $tab = 'currency'): void
    {
        $this->tab = $tab;
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function switchLanguage(string $code): void
    {
        // Flag as a manual choice so the country-detection middleware doesn't
        // overwrite it on the next request when the visitor's country changes.
        session([
            'storefront_language'        => $code,
            'storefront_language_manual' => true,
        ]);
        app()->setLocale($code);

        $customer = Auth::guard('storefront')->user();
        if ($customer) {
            $customer->update(['language' => $code]);
        }

        $this->open = false;
        $this->dispatch('localeChanged');
        $this->redirectToCurrentStorefrontPage();
    }

    public function switchCurrency(string $code): void
    {
        session([
            'storefront_currency' => $code,
            'storefront_currency_manual' => true,
        ]);
        $this->open = false;
        $this->dispatch('localeChanged');
        $this->redirectToCurrentStorefrontPage();
    }

    public function switchCountry(int $id): void
    {
        session(['storefront_country_id' => $id]);
        $this->open = false;
        $this->dispatch('localeChanged');
        $this->redirectToCurrentStorefrontPage();
    }

    protected function redirectToCurrentStorefrontPage(): void
    {
        $this->redirect($this->currentStorefrontUrl());
    }

    protected function currentStorefrontUrl(): string
    {
        $fallback = route('tenant.home');
        $referer = request()->header('referer');

        if (!is_string($referer) || $referer === '') {
            return $fallback;
        }

        $refererHost = parse_url($referer, PHP_URL_HOST);
        $refererPath = parse_url($referer, PHP_URL_PATH) ?? '';

        if ($refererHost !== null && $refererHost !== request()->getHost()) {
            return $fallback;
        }

        if (str_contains($refererPath, 'livewire') && str_ends_with($refererPath, '/update')) {
            return $fallback;
        }

        return $referer;
    }

    public function render()
    {
        $repo = app(StorefrontRepository::class);

        return view('livewire.tenant.storefront.layout.locale-switcher', [
            'languages' => $repo->activeLanguages(),
            'currencies' => $repo->activeCurrencies(),
            'currentLanguage' => $repo->currentLanguage(),
            'currentCurrency' => $repo->currentCurrency(),
            'countries' => \App\Models\Country::query()->with('translations.language')->orderBy('name')->get(['id', 'name', 'iso2', 'flag_emoji']),
            'currentCountryId' => session('storefront_country_id'),
        ]);
    }
}
