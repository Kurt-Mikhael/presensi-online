<?php

return [
    'stripe' => ['key' => env('STRIPE_KEY'), 'secret' => env('STRIPE_SECRET')],
    'ses' => ['key' => env('SES_KEY'), 'secret' => env('SES_SECRET'), 'region' => env('SES_REGION', 'us-east-1')],
    'postmark' => ['token' => env('POSTMARK_TOKEN')],
    'resend' => ['key' => env('RESEND_KEY')],
    'slack' => ['notifications' => ['bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN')]],
];