<div>
    <section class="pt-28 pb-14 bg-linear-to-br from-orange-50 to-white text-center">
        <div class="max-w-2xl mx-auto px-4">
            <span class="text-primary text-sm font-semibold tracking-wide uppercase">{{ __('Get in Touch') }}</span>
            <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 mt-3 mb-4">
                {{ __('Talk to the') }} {{ $marketplaceName }} {{ __('Team') }}
            </h1>
            <p class="text-gray-500 text-lg">
                {{ __('Questions about plans, onboarding, or store setup? Send a message and we will respond as quickly as possible.') }}
            </p>
        </div>
    </section>

    <section class="py-16 bg-white">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
                <div>
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-6">{{ __('Contact Information') }}</h2>
                    <ul class="space-y-6">
                        <li class="flex items-start gap-5">
                            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center shrink-0">
                                <i class="fas fa-map-marker-alt text-primary text-lg"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 mb-0.5">{{ __('Office Address') }}</p>
                                <p class="text-gray-500 text-sm">{{ $officeAddress }}</p>
                            </div>
                        </li>
                        <li class="flex items-start gap-5">
                            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center shrink-0">
                                <i class="fas fa-phone text-primary text-lg"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 mb-0.5">{{ __('Phone') }}</p>
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $officePhone) }}"
                                    class="text-gray-500 text-sm hover:text-primary transition-colors">{{ $officePhone }}</a>
                            </div>
                        </li>
                        <li class="flex items-start gap-5">
                            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center shrink-0">
                                <i class="fas fa-envelope text-primary text-lg"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 mb-0.5">{{ __('Email address') }}</p>
                                <a href="mailto:{{ $supportEmail }}"
                                    class="text-gray-500 text-sm hover:text-primary transition-colors">
                                    {{ $supportEmail ?: __('Not configured') }}
                                </a>
                            </div>
                        </li>
                        <li class="flex items-start gap-5">
                            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center shrink-0">
                                <i class="fas fa-clock text-primary text-lg"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 mb-0.5">{{ __('Office Hours') }}</p>
                                <p class="text-gray-500 text-sm">{{ $officeHours }}</p>
                            </div>
                        </li>
                    </ul>
                    <div class="mt-8 pt-8 border-t border-gray-100">
                        <a href="{{ route('website.register') }}"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline">
                            {{ __('Get Started Free') }} <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-2xl p-8">
                    <div class="flex items-center justify-between gap-4 mb-6">
                        <h2 class="text-2xl font-extrabold text-gray-900">{{ __('Send a Message') }}</h2>
                        @if($sent)
                            <button type="button" wire:click="$set('sent', false)"
                                class="text-xs font-semibold text-primary hover:underline">
                                {{ __('Send another message') }}
                            </button>
                        @endif
                    </div>

                    @if($sent)
                        <div class="mb-6 rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-700">
                            ✓ {{ __('Message sent successfully!') }} {{ __("We'll get back to you shortly.") }}
                        </div>
                    @endif

                    <form wire:submit.prevent="send" class="space-y-5">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                {{ __('Your Name') }} <span class="text-primary">*</span>
                            </label>
                            <input wire:model.live="contactName" type="text" placeholder="{{ __('Your Name') }}"
                                class="w-full px-4 py-3 rounded-xl border text-sm outline-none transition-all bg-white {{ $errors->has('contactName') ? 'border-red-400 bg-red-50' : 'border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20' }}">
                            @error('contactName')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                {{ __('Email address') }} <span class="text-primary">*</span>
                            </label>
                            <input wire:model.live="contactEmail" type="email" placeholder="you@example.com"
                                class="w-full px-4 py-3 rounded-xl border text-sm outline-none transition-all bg-white {{ $errors->has('contactEmail') ? 'border-red-400 bg-red-50' : 'border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20' }}">
                            @error('contactEmail')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                {{ __('Subject') }} <span class="text-primary">*</span>
                            </label>
                            <select wire:model="contactSubject"
                                class="w-full px-4 py-3 rounded-xl border text-sm outline-none transition-all bg-white text-gray-700 {{ $errors->has('contactSubject') ? 'border-red-400 bg-red-50' : 'border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20' }}">
                                <option value="">{{ __('Choose option') }}</option>
                                <option value="Technical Support">{{ __('Technical Support') }}</option>
                                <option value="Billing &amp; Payments">{{ __('Billing & Payments') }}</option>
                                <option value="Sales Inquiry">{{ __('Sales Inquiry') }}</option>
                                <option value="Partnership">{{ __('Partnership') }}</option>
                                <option value="Other">{{ __('Other') }}</option>
                            </select>
                            @error('contactSubject')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                                {{ __('Message') }} <span class="text-primary">*</span>
                            </label>
                            <textarea wire:model.live="contactMessage" rows="6" placeholder="{{ __('Message') }}..."
                                class="w-full px-4 py-3 rounded-xl border text-sm outline-none transition-all bg-white resize-none {{ $errors->has('contactMessage') ? 'border-red-400 bg-red-50' : 'border-gray-200 focus:border-primary focus:ring-2 focus:ring-primary/20' }}"></textarea>
                            @error('contactMessage')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" wire:loading.attr="disabled"
                            class="btn-primary w-full py-3.5 font-semibold rounded-xl text-base disabled:opacity-70">
                            <span wire:loading.remove wire:target="send">
                                <i class="fas fa-paper-plane me-2"></i>{{ __('Send Message') }}
                            </span>
                            <span wire:loading wire:target="send">{{ __('Sending…') }}</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
