<h2>{{ __('Pixel & Analytics Setup') }}</h2>
<p>{{ __('Connect marketing pixels to track visitors, add-to-carts, and purchases across Facebook, TikTok, Snapchat, and Google Analytics 4 — without touching any code.') }}</p>

<h3>{{ __('Where to enter your pixel IDs') }}</h3>
<p>{{ __('Go to') }} <strong>{{ __('Settings → Tracking') }}</strong> {{ __('and enter the ID for each platform you use.') }}</p>

<h3>{{ __('What gets tracked automatically') }}</h3>
<ul>
    <li><strong>{{ __('Page View') }}</strong> {{ __('— every page visit') }}</li>
    <li><strong>{{ __('View Content') }}</strong> {{ __('— when a customer opens a product page') }}</li>
    <li><strong>{{ __('Add to Cart') }}</strong> {{ __('— when a product is added to the cart') }}</li>
    <li><strong>{{ __('Initiate Checkout') }}</strong> {{ __('— when the customer enters the checkout') }}</li>
    <li><strong>{{ __('Purchase') }}</strong> {{ __('— when the order is confirmed (fires once per order)') }}</li>
</ul>

<h3>{{ __('How to find your pixel IDs') }}</h3>
<ul>
    <li><strong>{{ __('Facebook/Meta Pixel:') }}</strong> {{ __('Meta Business Suite → Events Manager → Your pixel → copy the 15–16 digit number') }}</li>
    <li><strong>{{ __('TikTok Pixel:') }}</strong> {{ __('TikTok Ads Manager → Assets → Events → Web Events → copy the pixel ID') }}</li>
    <li><strong>{{ __('Snapchat Pixel:') }}</strong> {{ __('Snapchat Ads Manager → Assets → Snap Pixel → copy the UUID') }}</li>
    <li><strong>{{ __('Google Analytics 4:') }}</strong> {{ __('GA4 property → Admin → Data Streams → your stream → Measurement ID (starts with') }} <code>G-</code>)</li>
</ul>

<h3>{{ __('Verifying pixels are firing') }}</h3>
<ul>
    <li><strong>{{ __('Meta:') }}</strong> {{ __('Install the Meta Pixel Helper Chrome extension and visit your storefront') }}</li>
    <li><strong>{{ __('TikTok:') }}</strong> {{ __('Install the TikTok Pixel Helper Chrome extension') }}</li>
    <li><strong>{{ __('GA4:') }}</strong> {{ __('Open GA4 → Reports → Realtime while visiting your storefront') }}</li>
</ul>
