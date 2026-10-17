<?php

return [
    'repository' => env('NOTES_REPOSITORY', 'minhxuvi/minh-notes-demo'),
    'branch' => env('NOTES_BRANCH', 'main'),
    'owner_id' => (string) env('GITHUB_OWNER_ID', '18323356'),
    'credentials' => storage_path('app/github-app.json'),
    'demo_only' => (bool) env('NOTES_DEMO_ONLY', true),
    'allowed_repositories' => ['minhxuvi/minh-notes-demo', 'minhxuvi/minhxuvi'],
    'cache_enabled' => env('APP_ENV') !== 'testing',
    'max_note_bytes' => 2_000_000,
];
