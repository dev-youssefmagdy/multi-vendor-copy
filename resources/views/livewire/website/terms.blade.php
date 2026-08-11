<div>
    <section class="pt-28 pb-12 bg-linear-to-br from-orange-50 to-white text-center">
        <div class="max-w-2xl mx-auto px-4" data-reveal>
            <span class="text-primary text-sm font-semibold tracking-wide uppercase">{{ __('Legal') }}</span>
            <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 mt-3 mb-3">{{ __('Terms & Conditions') }}</h1>
            <p class="text-gray-400 text-sm">{{ __('Last updated') }}: April 2025</p>
        </div>
    </section>

    <!-- CONTENT -->
    <section class="py-14 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-12">

                <!-- Sticky sidebar TOC -->
                <aside class="lg:col-span-1" data-reveal>
                    <div class="sticky top-24 bg-gray-50 rounded-2xl p-5">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">{{ __('Contents') }}
                        </p>
                        <ul class="space-y-1 text-sm">
                            <li><a href="#acceptance"
                                    class="block py-1.5 px-3 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ __('1. Acceptance of Terms') }}</a>
                            </li>
                            <li><a href="#account"
                                    class="block py-1.5 px-3 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ __('2. Account Responsibility') }}</a>
                            </li>
                            <li><a href="#service"
                                    class="block py-1.5 px-3 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ __('3. Service Availability') }}</a>
                            </li>
                            <li><a href="#ip"
                                    class="block py-1.5 px-3 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ __('4. Intellectual Property') }}</a>
                            </li>
                            <li><a href="#payment"
                                    class="block py-1.5 px-3 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ __('5. Payment Terms') }}</a>
                            </li>
                            <li><a href="#liability"
                                    class="block py-1.5 px-3 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ __('6. Limitation of Liability') }}</a>
                            </li>
                            <li><a href="#modifications"
                                    class="block py-1.5 px-3 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ __('7. Modifications') }}</a>
                            </li>
                            <li><a href="#governing"
                                    class="block py-1.5 px-3 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">{{ __('8. Governing Law') }}</a>
                            </li>
                        </ul>
                    </div>
                </aside>

                <!-- Terms content -->
                <div class="lg:col-span-3 prose prose-gray max-w-none">
                    <div class="bg-orange-50 border border-orange-100 rounded-2xl p-5 mb-8 text-sm text-gray-600"
                        data-reveal>
                        <i class="fas fa-info-circle text-primary mr-2"></i>
                        {{ __('Welcome to Ecommet. By accessing or using our services, you agree to be bound by the following Terms and Conditions. Please read them carefully before proceeding.') }}
                    </div>

                    <div id="acceptance" class="mb-8" data-reveal>
                        <h2 class="text-xl font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="w-7 h-7 bg-primary text-white rounded-lg inline-flex items-center justify-center text-sm font-bold shrink-0">1</span>
                            {{ __('Acceptance of Terms') }}
                        </h2>
                        <p class="text-gray-600 leading-relaxed">
                            {{ __('By using this website, you agree to comply with and be bound by these terms. If you do not agree, please refrain from using our services. Your continued use of the platform following the posting of any changes to these terms constitutes your acceptance of those changes.') }}
                        </p>
                    </div>

                    <div id="account" class="mb-8" data-reveal>
                        <h2 class="text-xl font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="w-7 h-7 bg-primary text-white rounded-lg inline-flex items-center justify-center text-sm font-bold shrink-0">2</span>
                            {{ __('Account Responsibility') }}
                        </h2>
                        <p class="text-gray-600 leading-relaxed">
                            {{ __('You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account. You agree to notify us immediately of any unauthorized use of your account. We reserve the right to terminate accounts that violate these terms.') }}
                        </p>
                    </div>

                    <div id="service" class="mb-8" data-reveal>
                        <h2 class="text-xl font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="w-7 h-7 bg-primary text-white rounded-lg inline-flex items-center justify-center text-sm font-bold shrink-0">3</span>
                            {{ __('Service Availability') }}
                        </h2>
                        <p class="text-gray-600 leading-relaxed">
                            {{ __('We strive to provide uninterrupted service, but we do not guarantee that the website will be available at all times or free from errors. We may temporarily suspend access for maintenance, upgrades, or other technical reasons without prior notice. We shall not be liable for any downtime or service interruptions.') }}
                        </p>
                    </div>

                    <div id="ip" class="mb-8" data-reveal>
                        <h2 class="text-xl font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="w-7 h-7 bg-primary text-white rounded-lg inline-flex items-center justify-center text-sm font-bold shrink-0">4</span>
                            {{ __('Intellectual Property') }}
                        </h2>
                        <p class="text-gray-600 leading-relaxed">
                            {{ __('All content on this website, including but not limited to text, images, logos, and designs, is protected by intellectual property laws. You may not use, reproduce, distribute, or modify any content without prior written permission from Ecommet. User-generated content remains owned by the user but grants Ecommet a license to display it on the platform.') }}
                        </p>
                    </div>

                    <div id="payment" class="mb-8" data-reveal>
                        <h2 class="text-xl font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="w-7 h-7 bg-primary text-white rounded-lg inline-flex items-center justify-center text-sm font-bold shrink-0">5</span>
                            {{ __('Payment Terms') }}
                        </h2>
                        <p class="text-gray-600 leading-relaxed">
                            {{ __('Payments for services are processed securely through our payment partners. By subscribing to a paid plan, you agree to pay all fees as described at the time of purchase. All fees are non-refundable except as outlined in our 14-day money-back guarantee policy. Prices are subject to change with 30 days\' notice.') }}
                        </p>
                    </div>

                    <div id="liability" class="mb-8" data-reveal>
                        <h2 class="text-xl font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="w-7 h-7 bg-primary text-white rounded-lg inline-flex items-center justify-center text-sm font-bold shrink-0">6</span>
                            {{ __('Limitation of Liability') }}
                        </h2>
                        <p class="text-gray-600 leading-relaxed">
                            {{ __('To the fullest extent permitted by applicable law, Ecommet shall not be liable for any indirect, incidental, special, consequential, or punitive damages arising from your use of our services or website. Our total liability to you for any claim shall not exceed the amount you paid to us in the twelve months preceding the claim.') }}
                        </p>
                    </div>

                    <div id="modifications" class="mb-8" data-reveal>
                        <h2 class="text-xl font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="w-7 h-7 bg-primary text-white rounded-lg inline-flex items-center justify-center text-sm font-bold shrink-0">7</span>
                            {{ __('Modifications') }}
                        </h2>
                        <p class="text-gray-600 leading-relaxed">
                            {{ __('We reserve the right to modify these terms at any time. Any changes will be posted on this page with an updated revision date. We encourage you to review these terms periodically. Your continued use of the platform after any modifications constitutes your acceptance of the new terms.') }}
                        </p>
                    </div>

                    <div id="governing" class="mb-8" data-reveal>
                        <h2 class="text-xl font-extrabold text-gray-900 mb-3 flex items-center gap-2">
                            <span
                                class="w-7 h-7 bg-primary text-white rounded-lg inline-flex items-center justify-center text-sm font-bold shrink-0">8</span>
                            {{ __('Governing Law') }}
                        </h2>
                        <p class="text-gray-600 leading-relaxed">
                            {{ __('These Terms and Conditions are governed by and construed in accordance with applicable laws. Any disputes arising under or in connection with these terms shall be subject to the exclusive jurisdiction of the competent courts. If any provision of these terms is held to be invalid, the remaining provisions shall remain in full force and effect.') }}
                        </p>
                    </div>

                    <div class="bg-gray-50 rounded-2xl p-6 text-sm text-gray-500" data-reveal>
                        <p>{{ __('If you have any questions about these Terms and Conditions, please') }} <a
                                href="{{ route('website.contact') }}"
                                class="text-primary hover:underline">{{ __('contact us') }}</a>.</p>
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