<?php

return [
    'android_package' => env('MOBILE_ANDROID_PACKAGE'),
    // Comma separated SHA-256 cert fingerprints.
    'android_sha256' => env('MOBILE_ANDROID_SHA256'),
    // "<TeamID>.<BundleID>"
    'ios_app_id' => env('MOBILE_IOS_APP_ID'),
];
