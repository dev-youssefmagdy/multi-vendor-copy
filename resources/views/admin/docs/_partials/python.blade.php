<h2>Python Modules</h2>

<p>Located at <code>python-modules/</code>. These provide auxiliary AI/scraping helpers consumed by the Laravel app.</p>

<h3>Files</h3>
<table>
    <tr>
        <th>File</th>
        <th>Purpose</th>
    </tr>
    <tr>
        <td><code>app.py</code></td>
        <td>Main entry point; wires helper services into a single CLI/HTTP surface.</td>
    </tr>
    <tr>
        <td><code>price_finder.py</code></td>
        <td>Scrapes/parses competitor pricing data for a given product or URL.</td>
    </tr>
    <tr>
        <td><code>social_post_generator.py</code></td>
        <td>Generates social media post copy from product info / prompts.</td>
    </tr>
    <tr>
        <td><code>requirements.txt</code></td>
        <td>Python dependencies. Install with <code>pip install -r requirements.txt</code>.</td>
    </tr>
    <tr>
        <td><code>README.md</code></td>
        <td>Setup, environment variables, and run instructions for the modules.</td>
    </tr>
</table>

<h3>Setup</h3>
<pre>cd python-modules
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
python app.py</pre>

<h3>Calling from PHP</h3>
<p>Invoke via <code>Symfony\Component\Process\Process</code> or expose as HTTP endpoints. Make sure the Python venv is
    reachable from PHP-FPM/cli context (absolute paths recommended in production).</p>

<h3>Environment variables</h3>
<p>Each script reads its API keys (OpenAI, scraping endpoints, etc.) from environment variables — see
    <code>python-modules/README.md</code> for the exact names.</p>