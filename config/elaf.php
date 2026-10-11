<?php

declare(strict_types=1);

/*
 * Editorial switches. Read through the EditorialSettings / ContributorSettings
 * ports; the Settings module (Phase 5) moves them into the database.
 */
return [
    'contributors' => [
        'require_account_approval' => (bool) env('ELAF_CONTRIBUTORS_REQUIRE_ACCOUNT_APPROVAL', true),
        'editor_can_publish' => (bool) env('ELAF_CONTRIBUTORS_EDITOR_CAN_PUBLISH', false),
        'guest_byline_label' => (bool) env('ELAF_CONTRIBUTORS_GUEST_BYLINE_LABEL', true),
    ],

    'turnstile' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],
];
