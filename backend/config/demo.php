<?php

return [
    'enabled' => (bool) env('SEED_DEMO_RESTAURANT', false),
    'owner_email' => env('DEMO_OWNER_EMAIL', 'demo@menuos.app'),
    'owner_password' => env('DEMO_OWNER_PASSWORD'),
];
