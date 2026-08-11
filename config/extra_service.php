<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Python AI Microservice
    |--------------------------------------------------------------------------
    | URL of the FastAPI service running in python-modules/.
    | Set PYTHON_SERVICE_URL in .env (e.g. http://127.0.0.1:8008).
    | SERVICE_TOKEN must match the value in python-modules/.env when auth is
    | enabled on the Python side.
    */

    'python_service' => [
        'url' => env('PYTHON_MODULES_URL', 'http://127.0.0.1:8008'),
        'token' => env('PYTHON_MODULES_TOKEN', ''),
        'timeout' => (int) env('PYTHON_SERVICE_TIMEOUT', 60),
    ],

];
