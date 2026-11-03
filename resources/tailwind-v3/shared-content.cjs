// Everything a storefront page can render. The runtime Tailwind v3 compiler scanned the
// live DOM; the build scans the same templates and scripts instead.
module.exports = (theme) => [
    `./resources/views/themes/${theme}/**/*.blade.php`,
    './resources/views/components/**/*.blade.php',
    './resources/views/storefront/**/*.blade.php',
    './resources/views/livewire/tenant/storefront/**/*.blade.php',
    './resources/views/vendor/**/*.blade.php',
    './resources/views/errors/**/*.blade.php',
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './app/**/*.php',
    './resources/js/**/*.js',
    './public/js/*.js',
];
