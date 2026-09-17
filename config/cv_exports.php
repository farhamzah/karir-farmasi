<?php

$browserCandidates = array_filter([
    env('CV_CHROMIUM_PATH'),
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    '/usr/bin/chromium',
    '/usr/bin/google-chrome',
]);

return [
    'chromium_path' => collect($browserCandidates)->first(fn (string $path) => is_file($path)),
    'timeout_seconds' => (int) env('CV_PDF_TIMEOUT', 45),
];
