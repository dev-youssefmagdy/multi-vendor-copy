<div>
    <section class="pt-28 pb-12 bg-linear-to-br from-orange-50 to-white text-center">
        <div class="max-w-2xl mx-auto px-4" data-reveal>
            <span class="text-primary text-sm font-semibold tracking-wide uppercase">{{ __('Legal') }}</span>
            <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 mt-3 mb-3">{{ __('Privacy Policy') }}</h1>
            <p class="text-gray-400 text-sm">{{ __('Last updated') }}: April 2025</p>
        </div>
    </section>

    <section class="py-14 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-12">

                <aside class="lg:col-span-1" data-reveal>
                    <div class="sticky top-24 bg-gray-50 rounded-2xl p-5">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">{{ __('Contents') }}</p>
                        <ul class="space-y-1 text-sm">
                            <li><a href="#information" class="block py-1.5 px-3 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ __('1. Information We Collect') }}</a></li>
                            <li><a href="#use" class="block py-1.5 px-3 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ __('2. How We Use Your Information') }}</a></li>
                            <li><a href="#sharing" class="block py-1.5 px-3 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ __('3. Information Sharing') }}</a></li>
                            <li><a href="#cookies" class="block py-1.5 px-3 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ __('4. Cookies & Tracking') }}</a></li>
                            <li><a href="#security" class="block py-1.5 px-3 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ __('5. Data Security') }}</a></li>
                            <li><a href="#retention" class="block py-1.5 px-3 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ __('6. Data Retention') }}</a></li>
                            <li><a href="#rights" class="block py-1.5 px-3 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ __('7. Your Rights') }}</a></li>
                            <li><a href="#children" class="block py-1.5 px-3 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ __("8. Children's Privacy") }}</a></li>
                            <li><a href="#changes" class="block py-1.5 px-3 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ __('9. Changes to This Policy') }}</a></li>
                        </ul>
                    </div>
                </aside>

                <div class="lg:col-span-3 prose prose-gray max-w-none">
                    <div class="bg-orange-50 border border-orange-100 rounded-2xl p-5 mb-8 text-sm text-gray-600" data-reveal>
                        <i class="fas fa-shield-alt text-primary mr-2"></i>
                        {{ __('Your privacy is important to us. This policy explains how we collect, use, and protect your personal information when you use our platform.') }}
                    </div>

                    <div id="information" class="mb-8" data-reveal>
                        <h2 class="text-xl font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                            <span class="w-7 h-7 bg-primary text-white rounded-lg inline-flex items-center justify-center text-sm font-bold shrink-0">1</span>
                            {{ __('Information We Collect') }}
                        </h2>
                        <p class="text-gray-600 leading-relaxed mb-3">{{ __('We collect information you provide directly, such as when you register an account, set up a store, or contact us. This includes:') }}</p>
                        <ul class="list-disc list-inside text-gray-600 space-y-1.5 text-sm">
                            <li>{{ __('Name, email address, and password') }}</li>
                            <li>{{ __('Store name, domain preferences, and business details') }}</li>
                            <li>{{ __('Payment information processed securely by our payment partners') }}</li>
                            <li>{{ __('Communications you send to us, including support requests') }}</li>
                        </ul>
                        <p class="text-gray-600 leading-relaxed mt-3">{{ __('We also collect usage data automatically, such as IP address, browser type, pages visited, and actions taken on the platform.') }}</p>
                    </div>

                    <div id="use" class="mb-8" data-reveal>
                        <h2 class="text-xl font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                            <span class="w-7 h-7 bg-primary text-white rounded-lg inline-flex items-center justify-center text-sm font-bold shrink-0">2</span>
                            {{ __('How We Use Your Information') }}
                        </h2>
                        <p class="text-gray-600 leading-relaxed mb-3">{{ __('We use the information we collect to:') }}</p>
                        <ul class="list-disc list-inside text-gray-600 space-y-1.5 text-sm">
                            <li>{{ __('Provision and maintain your store and account') }}</li>
                            <li>{{ __('Process payments and send transactional emails') }}</li>
                            <li>{{ __('Provide customer support and respond to your requests') }}</li>
                            <li>{{ __('Improve our platform, features, and user experience') }}</li>
                            <li>{{ __('Send service announcements and optional marketing updates (you may opt out at any time)') }}</li>
                            <li>{{ __('Comply with legal obligations and enforce our terms') }}</li>
                        </ul>
                    </div>

                    <div id="sharing" class="mb-8" data-reveal>
                        <h2 class="text-xl font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                            <span class="w-7 h-7 bg-primary text-white rounded-lg inline-flex items-center justify-center text-sm font-bold shrink-0">3</span>
                            {{ __('Information Sharing') }}
                        </h2>
                        <p class="text-gray-600 leading-relaxed">{{ __('We do not sell, rent, or trade your personal data to third parties. We may share your information with trusted service providers who assist us in operating the platform (e.g. payment processors, hosting providers, email delivery services). These providers are contractually bound to handle your data securely and only for the purposes we specify. We may also disclose information when required by law or to protect the rights and safety of our users.') }}</p>
                    </div>

                    <div id="cookies" class="mb-8" data-reveal>
                        <h2 class="text-xl font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                            <span class="w-7 h-7 bg-primary text-white rounded-lg inline-flex items-center justify-center text-sm font-bold shrink-0">4</span>
                            {{ __('Cookies & Tracking') }}
                        </h2>
                        <p class="text-gray-600 leading-relaxed">{{ __('We use cookies and similar tracking technologies to maintain sessions, remember your preferences, and analyse how our platform is used. Essential cookies are required for the platform to function. Analytics cookies help us understand usage patterns and improve the service. You can control non-essential cookies through your browser settings, though disabling them may affect some functionality.') }}</p>
                    </div>

                    <div id="security" class="mb-8" data-reveal>
                        <h2 class="text-xl font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                            <span class="w-7 h-7 bg-primary text-white rounded-lg inline-flex items-center justify-center text-sm font-bold shrink-0">5</span>
                            {{ __('Data Security') }}
                        </h2>
                        <p class="text-gray-600 leading-relaxed">{{ __('We implement industry-standard security measures to protect your personal information, including encryption in transit (TLS), hashed passwords, and access controls. Each merchant store operates in an isolated database environment. While we take all reasonable precautions, no method of transmission over the internet is 100% secure and we cannot guarantee absolute security.') }}</p>
                    </div>

                    <div id="retention" class="mb-8" data-reveal>
                        <h2 class="text-xl font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                            <span class="w-7 h-7 bg-primary text-white rounded-lg inline-flex items-center justify-center text-sm font-bold shrink-0">6</span>
                            {{ __('Data Retention') }}
                        </h2>
                        <p class="text-gray-600 leading-relaxed">{{ __('We retain your personal data for as long as your account is active or as needed to provide services. If you close your account, we will delete or anonymise your data within a reasonable period, except where we are required to retain it for legal, tax, or accounting obligations. Backups may persist for a short additional period.') }}</p>
                    </div>

                    <div id="rights" class="mb-8" data-reveal>
                        <h2 class="text-xl font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                            <span class="w-7 h-7 bg-primary text-white rounded-lg inline-flex items-center justify-center text-sm font-bold shrink-0">7</span>
                            {{ __('Your Rights') }}
                        </h2>
                        <p class="text-gray-600 leading-relaxed mb-3">{{ __('Depending on your location, you may have the right to:') }}</p>
                        <ul class="list-disc list-inside text-gray-600 space-y-1.5 text-sm">
                            <li>{{ __('Access the personal data we hold about you') }}</li>
                            <li>{{ __('Request correction of inaccurate data') }}</li>
                            <li>{{ __('Request deletion of your data') }}</li>
                            <li>{{ __('Object to or restrict how we process your data') }}</li>
                            <li>{{ __('Receive a portable copy of your data') }}</li>
                            <li>{{ __('Withdraw consent where processing is based on consent') }}</li>
                        </ul>
                        <p class="text-gray-600 leading-relaxed mt-3">{{ __('To exercise any of these rights, please contact us through the contact page.') }}</p>
                    </div>

                    <div id="children" class="mb-8" data-reveal>
                        <h2 class="text-xl font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                            <span class="w-7 h-7 bg-primary text-white rounded-lg inline-flex items-center justify-center text-sm font-bold shrink-0">8</span>
                            {{ __("Children's Privacy") }}
                        </h2>
                        <p class="text-gray-600 leading-relaxed">{{ __('Our platform is not directed at children under the age of 16. We do not knowingly collect personal information from children. If we become aware that a child has provided us with personal data, we will take steps to delete it promptly. If you believe a child has submitted information to us, please contact us immediately.') }}</p>
                    </div>

                    <div id="changes" class="mb-8" data-reveal>
                        <h2 class="text-xl font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                            <span class="w-7 h-7 bg-primary text-white rounded-lg inline-flex items-center justify-center text-sm font-bold shrink-0">9</span>
                            {{ __('Changes to This Policy') }}
                        </h2>
                        <p class="text-gray-600 leading-relaxed">{{ __('We may update this Privacy Policy from time to time to reflect changes in our practices or for legal reasons. We will post the revised policy on this page with an updated date. We encourage you to review this page periodically. Continued use of the platform after changes are posted constitutes your acceptance of the updated policy.') }}</p>
                    </div>

                    <div class="bg-gray-50 rounded-2xl p-6 text-sm text-gray-500" data-reveal>
                        <p>{{ __('If you have any questions about this Privacy Policy, please') }} <a href="{{ route('website.contact') }}" class="text-primary hover:underline">{{ __('contact us') }}</a>.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <script>
        (function () {
            const links = document.querySelectorAll('aside a[href^="#"]');
            const sections = Array.from(links).map(l => document.querySelector(l.getAttribute('href')));

            function setActive(id) {
                links.forEach(l => {
                    const active = l.getAttribute('href') === '#' + id;
                    l.classList.toggle('text-primary', active);
                    l.classList.toggle('bg-orange-50', active);
                    l.classList.toggle('font-medium', active);
                    l.classList.toggle('text-gray-600', !active);
                });
            }

            const observer = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) setActive(entry.target.id);
                });
            }, { rootMargin: '-20% 0px -70% 0px', threshold: 0 });

            sections.forEach(s => s && observer.observe(s));
        })();
    </script>
</div>
