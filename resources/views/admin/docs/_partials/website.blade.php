<h2>Central Marketing Website</h2>

<p>The central website is everything served on the central domain outside <code>/admin</code>. Pages are Livewire
    components under <code>app/Livewire/Website/</code> and routes are declared at the top of
    <code>routes/central.php</code>.</p>

<table>
    <tr>
        <th>Route</th>
        <th>Component</th>
        <th>Purpose</th>
    </tr>
    <tr>
        <td><code>/</code></td>
        <td><code>LandingPage</code></td>
        <td>Marketing hero, feature highlights, CTA to sign up.</td>
    </tr>
    <tr>
        <td><code>/about</code></td>
        <td><code>AboutPage</code></td>
        <td>About-us copy / company info.</td>
    </tr>
    <tr>
        <td><code>/contact</code></td>
        <td><code>ContactPage</code></td>
        <td>Contact form.</td>
    </tr>
    <tr>
        <td><code>/pricing</code></td>
        <td><code>PricingPage</code></td>
        <td>Plans and feature comparison.</td>
    </tr>
    <tr>
        <td><code>/how-it-works</code></td>
        <td><code>HowItWorksPage</code></td>
        <td>Onboarding/feature explainer.</td>
    </tr>
    <tr>
        <td><code>/faqs</code></td>
        <td><code>FaqsPage</code></td>
        <td>Public FAQ entries (admin-managed).</td>
    </tr>
    <tr>
        <td><code>/terms</code></td>
        <td><code>TermsPage</code></td>
        <td>Static legal page.</td>
    </tr>
    <tr>
        <td><code>/blog</code></td>
        <td><code>BlogListPage</code></td>
        <td>Blog index from central blog tables.</td>
    </tr>
    <tr>
        <td><code>/blog/&lbrace;slug&rbrace;</code></td>
        <td><code>BlogDetailPage</code></td>
        <td>Blog detail.</td>
    </tr>
    <tr>
        <td><code>/register</code></td>
        <td><code>RegisterPage</code></td>
        <td>Tenant signup wizard (plan + store details + payment).</td>
    </tr>
    <tr>
        <td><code>/register/payment/...</code></td>
        <td><code>RegistrationPaymentController</code></td>
        <td>Charge / verify / cancel for the signup payment.</td>
    </tr>
    <tr>
        <td><code>/locale/&lbrace;locale&rbrace;</code></td>
        <td>closure</td>
        <td>Switches the session locale (en/ar).</td>
    </tr>
</table>

<h3>How content is sourced</h3>
<ul>
    <li>Static copy lives in the Blade view of each page.</li>
    <li>Dynamic content (FAQs, blog posts, static pages, plans, currencies) is read from central DB tables managed via
        the admin panel.</li>
    <li>Translations come from <code>lang/&lbrace;en,ar&rbrace;.json</code> + namespaced files. Use
        <code>__('key')</code>.</li>
</ul>