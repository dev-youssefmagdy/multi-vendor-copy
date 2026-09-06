<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Coming Soon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 to-slate-800 flex items-center justify-center p-4">
    <div class="text-center max-w-md">
        <div class="w-20 h-20 bg-white/10 rounded-2xl flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-store-slash text-white text-3xl opacity-60"></i>
        </div>

        <h1 class="text-2xl font-extrabold text-white mb-3">
            Configure website first
        </h1>
        <p class="text-slate-400 text-sm leading-relaxed mb-8">
            This store is still being set up and isn't available to the public yet.
            The store owner needs to complete setup and launch their storefront.
        </p>

        <a href="{{ $adminLoginUrl }}"
            class="inline-flex items-center gap-2.5 bg-white text-slate-900 font-bold px-6 py-3.5 rounded-xl text-sm hover:bg-slate-100 transition-colors">
            <i class="fas fa-tachometer-alt"></i>
            Go to Dashboard
        </a>

        <p class="text-slate-600 text-xs mt-8">
            If you're the store owner, log in and complete the onboarding wizard.
        </p>
    </div>
</body>
</html>
