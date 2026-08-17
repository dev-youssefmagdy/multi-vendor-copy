<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Temporarily Unavailable</title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 1rem;
            padding: 3rem 2.5rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }

        .icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 0.75rem;
        }

        p {
            font-size: 0.95rem;
            color: #94a3b8;
            line-height: 1.65;
            margin-bottom: 1rem;
        }

        .badge {
            display: inline-block;
            background: #be123c22;
            color: #fb7185;
            border: 1px solid #be123c55;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
        }

        .note {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #334155;
            font-size: 0.8rem;
            color: #64748b;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="icon">🔒</div>
        <div class="badge">Service Suspended</div>
        <h1>This store is temporarily unavailable</h1>
        <p>
            The store owner is in the process of updating their account settings.
            Normal service will resume shortly.
        </p>
        <p>
            If you are the store owner, please log in to your vendor panel and
            configure your own payment gateway credentials to restore your storefront.
        </p>
        <div class="note">
            Error 503 &mdash; Service Temporarily Unavailable
        </div>
    </div>
</body>

</html>