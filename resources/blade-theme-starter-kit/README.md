# Blade Theme Starter Kit

Zip up this folder's contents (not the folder itself) with your changes and
upload it from your vendor dashboard under **Store → Blade Theme**. Every
upload is queued for admin review — it will not appear on your storefront
until an admin approves it AND you activate it from your dashboard.

Required files (the upload is rejected without them):

- `layout/app.blade.php`
- `pages/home/index.blade.php`

Allowed file types: `.blade.php`, `.css`, `.js`, `.png`, `.jpg`, `.jpeg`,
`.gif`, `.svg`, `.webp`, `.woff`, `.woff2`.

Blade files may not contain raw PHP tags (`<?php`, `<?=`, `@php`) or common
shell/eval functions (`system(`, `exec(`, `eval(`, etc.) — these are rejected
at upload time. This is a first-pass filter only, not a full sandbox: your
theme is still reviewed by a human before it can go live.
