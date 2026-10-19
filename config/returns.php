<?php

return [
    /*
    | Evidence uploads on the storefront "Request a Return" form (sizes in kilobytes).
    | Livewire's temporary_file_upload.rules (config/livewire.php) must stay >= the larger of the two.
    */
    'photo_max_kb' => (int) env('RETURN_PHOTO_MAX_KB', 10240),
    'video_max_kb' => (int) env('RETURN_VIDEO_MAX_KB', 102400),
];
