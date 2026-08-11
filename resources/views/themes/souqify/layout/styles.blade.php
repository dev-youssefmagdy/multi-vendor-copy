<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
    href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Manrope:wght@400;600;700&display=swap"
    rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: {
                    sans: ['Outfit', 'system-ui', 'sans-serif'],
                    manrope: ['Manrope', 'sans-serif'],
                },
                colors: {
                    brand: {
                        blue: '#1d4ed8',
                        amber: '#f59e0b',
                        orange: '#ea580c',
                    }
                }
            }
        }
    }
</script>
<style>
    html {
        scroll-behavior: smooth;
    }

    body {
        font-family: 'Outfit', sans-serif;
    }

    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .marquee-track {
        animation: marquee 30s linear infinite;
    }

    @keyframes marquee {
        0% {
            transform: translateX(0);
        }

        100% {
            transform: translateX(-50%);
        }
    }

    .product-img-bg {
        background: linear-gradient(135deg, #f5f3ff 0%, #ede9fe 100%);
    }

    .hero-overlay {
        background: linear-gradient(90deg, rgba(8, 47, 73, 0.85) 0%, rgba(8, 47, 73, 0.4) 60%, rgba(8, 47, 73, 0) 100%);
    }
</style>