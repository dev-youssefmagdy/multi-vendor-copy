// Precompiles the Tailwind v3 CSS that ecommet, elora (v1) and souqify (v1) used to
// generate in the browser at runtime. Run by `npm run build`.
const { execFileSync } = require('child_process');
const path = require('path');
const root = path.resolve(__dirname, '../..');
const cli = path.join(root, 'node_modules/tailwindcss3/lib/cli.js');
for (const theme of ['ecommet', 'elora', 'souqify']) {
    execFileSync(process.execPath, [cli,
        '-c', path.join(__dirname, `${theme}.config.cjs`),
        '-i', path.join(__dirname, 'input.css'),
        '-o', path.join(root, `public/css/tw3-${theme}.css`),
        '--minify'], { cwd: root, stdio: 'inherit' });
}
