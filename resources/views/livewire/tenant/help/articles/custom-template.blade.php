<h2>Custom Template Upload</h2>
<p>Upload your own HTML/CSS/JS storefront and replace the system theme entirely.</p>

<h3>What's allowed in the ZIP</h3>
<ul>
    <li>An <code>index.html</code> file at the root of the ZIP — <strong>required</strong></li>
    <li><code>assets/</code>, <code>css/</code>, <code>js/</code>, <code>images/</code> folders</li>
    <li>Any static file types: HTML, CSS, JS, PNG, JPG, SVG, WEBP, WOFF, WOFF2</li>
</ul>

<h3>What's NOT allowed</h3>
<ul>
    <li>PHP files (<code>.php</code>, <code>.phtml</code>, <code>.phar</code>)</li>
    <li>Executable files (<code>.exe</code>, <code>.sh</code>, <code>.py</code>, <code>.rb</code>)</li>
    <li>Server config files (<code>.htaccess</code>, <code>.config</code>)</li>
    <li>Any folder not in the allowed list above</li>
    <li>Path traversal (<code>../</code>) in any filename</li>
</ul>

<div class="docs-step">
    <div class="docs-step-num">1</div>
    <div class="docs-step-body">
        <h3>Prepare your ZIP</h3>
        <p>Create a ZIP file with <code>index.html</code> at the root. Reference CSS with <code>css/style.css</code>, JS with <code>js/app.js</code>, images with <code>images/logo.png</code>.</p>
    </div>
</div>

<div class="docs-step">
    <div class="docs-step-num">2</div>
    <div class="docs-step-body">
        <h3>Upload the ZIP</h3>
        <p>Go to <strong>Store → Custom Template</strong>, click <em>Choose file</em>, select your ZIP (max 20MB), and click <strong>Upload Template</strong>.</p>
    </div>
</div>

<div class="docs-step">
    <div class="docs-step-num">3</div>
    <div class="docs-step-body">
        <h3>Wait for admin review</h3>
        <p>Your template is scanned and sent to our team for security review. Status starts as <span class="docs-badge docs-badge-pending">Pending</span>. You'll see <span class="docs-badge docs-badge-approved">Approved</span> once it's cleared, or <span class="docs-badge docs-badge-rejected">Rejected</span> with a reason.</p>
    </div>
</div>

<div class="docs-step">
    <div class="docs-step-num">4</div>
    <div class="docs-step-body">
        <h3>Preview & Activate</h3>
        <p>Click <strong>Preview</strong> to see your template in a sandbox iframe. Once you're ready, click <strong>Activate</strong> — your storefront will immediately use your uploaded template.</p>
    </div>
</div>

<div class="docs-step">
    <div class="docs-step-num">5</div>
    <div class="docs-step-body">
        <h3>Revert to a system theme</h3>
        <p>Click <strong>Deactivate</strong> at any time to switch back to your selected system theme (Elora, Souqify, or Ecommet).</p>
    </div>
</div>
