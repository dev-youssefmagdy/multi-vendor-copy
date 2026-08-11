@php
    use App\Repositories\AppSettingRepository;
    $footerSettings = app(AppSettingRepository::class)->getByKeys([
        'marketplace_name',
        'app_name',
        'support_email',
        'office_phone',
        'office_address',
        'facebook_url',
        'twitter_url',
        'instagram_url',
        'youtube_url',
        'linkedin_url',
        'footer_logo',
    ]);
    $brandName = $footerSettings->get('marketplace_name')?->value ?? $footerSettings->get('app_name')?->value ?? config('app.name', 'Ecommet');
    $supportEmail = $footerSettings->get('support_email')?->value ?? '';
    $officePhone = $footerSettings->get('office_phone')?->value ?? '';
    $officeAddress = $footerSettings->get('office_address')?->value ?? '';
    $fbUrl = $footerSettings->get('facebook_url')?->value ?? '#';
    $twUrl = $footerSettings->get('twitter_url')?->value ?? '#';
    $igUrl = $footerSettings->get('instagram_url')?->value ?? '#';
    $ytUrl = $footerSettings->get('youtube_url')?->value ?? '#';
    $liUrl = $footerSettings->get('linkedin_url')?->value ?? '#';
    $footerLogo = $footerSettings->get('footer_logo')?->value ?: asset('central-website/assets/image/central-footer-logo.png');
@endphp
<footer class="bg-gray-900 text-gray-400">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
            <!-- Company -->
            <div>
                <a href="{{ route('website.home') }}" class="inline-block mb-4">
                    <img src="{{ $footerLogo }}" alt="{{ $brandName }}" class="h-8 w-auto  ">
                </a>
                <p class="text-sm leading-relaxed mb-5">
                    {{ __('We are an award-winning multinational company. We believe in quality and world-class standards.') }}
                </p>
                <div class="flex gap-3">
                    <a href="{{ $fbUrl }}"
                        class="w-9 h-9 bg-gray-800 hover:bg-primary rounded-full flex items-center justify-center transition-colors">
                        <i class="fab fa-facebook-f text-sm"></i>
                    </a>
                    <a href="{{ $twUrl }}"
                        class="w-9 h-9 bg-gray-800 hover:bg-primary rounded-full flex items-center justify-center transition-colors">
                        <i class="fab fa-twitter text-sm"></i>
                    </a>
                    <a href="{{ $igUrl }}"
                        class="w-9 h-9 bg-gray-800 hover:bg-primary rounded-full flex items-center justify-center transition-colors">
                        <i class="fab fa-instagram text-sm"></i>
                    </a>
                    <a href="{{ $ytUrl }}"
                        class="w-9 h-9 bg-gray-800 hover:bg-primary rounded-full flex items-center justify-center transition-colors">
                        <i class="fab fa-youtube text-sm"></i>
                    </a>
                    <a href="{{ $liUrl }}"
                        class="w-9 h-9 bg-gray-800 hover:bg-primary rounded-full flex items-center justify-center transition-colors">
                        <i class="fab fa-linkedin-in text-sm"></i>
                    </a>
                </div>
            </div>
            <!-- Quick links -->
            <div>
                <h4 class="text-white font-bold mb-5">{{ __('Quick Links') }}</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="{{ route('website.home') }}"
                            class="hover:text-primary transition-colors">{{ __('Home') }}</a></li>
                    <li><a href="{{ route('website.pricing') }}"
                            class="hover:text-primary transition-colors">{{ __('Pricing') }}</a></li>
                    <li><a href="{{ route('website.how-it-works') }}"
                            class="hover:text-primary transition-colors">{{ __('How It Works') }}</a></li>
                    <li><a href="{{ route('website.blog') }}"
                            class="hover:text-primary transition-colors">{{ __('Blog') }}</a></li>
                </ul>
            </div>
            <!-- Company -->
            <div>
                <h4 class="text-white font-bold mb-5">{{ __('Company') }}</h4>
                <ul class="space-y-3 text-sm">
                    <li><a href="{{ route('website.about') }}"
                            class="hover:text-primary transition-colors">{{ __('About Us') }}</a></li>
                    <li><a href="{{ route('website.contact') }}"
                            class="hover:text-primary transition-colors">{{ __('Contact') }}</a></li>
                    <li><a href="{{ route('website.faqs') }}"
                            class="hover:text-primary transition-colors">{{ __('FAQs') }}</a></li>
                    <li><a href="{{ route('website.terms') }}"
                            class="hover:text-primary transition-colors">{{ __('Terms & Conditions') }}</a></li>
                </ul>
            </div>
            <!-- Contact -->
            <div>
                <h4 class="text-white font-bold mb-5">{{ __('Contact Info') }}</h4>
                <ul class="space-y-4 text-sm">
                    @if($officeAddress)
                        <li class="flex items-start gap-3">
                            <i class="fas fa-map-marker-alt text-primary mt-0.5 w-4 shrink-0"></i>
                            <span>{{ $officeAddress }}</span>
                        </li>
                    @endif
                    @if($officePhone)
                        <li class="flex items-center gap-3">
                            <i class="fas fa-phone text-primary w-4"></i>
                            <a href="tel:{{ $officePhone }}"
                                class="hover:text-primary transition-colors">{{ $officePhone }}</a>
                        </li>
                    @endif
                    @if($supportEmail)
                        <li class="flex items-center gap-3">
                            <i class="fas fa-envelope text-primary w-4"></i>
                            <a href="mailto:{{ $supportEmail }}"
                                class="hover:text-primary transition-colors">{{ $supportEmail }}</a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
    <div class="border-t border-gray-800">
        <div
            class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-sm">
            <span>&copy; {{ date('Y') }} {{ $brandName }}. {{ __('All rights reserved.') }}</span>
            <div class="flex gap-5">
                <a href="{{ route('website.privacy') }}"
                    class="hover:text-primary transition-colors">{{ __('Privacy Policy') }}</a>
                <a href="{{ route('website.terms') }}"
                    class="hover:text-primary transition-colors">{{ __('Terms of Use') }}</a>
            </div>
        </div>
    </div>
</footer>
