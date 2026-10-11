<?php

declare(strict_types=1);

/*
 * Editorial switches. Read through the EditorialSettings / ContributorSettings
 * ports; the Settings module (Phase 5) moves them into the database.
 */
return [
    /*
     * Site identity shown in the header and footer. Empty values are not
     * rendered. Moves to the settings table in Phase 5 (FR-SET-01).
     */
    'site' => [
        'logo_suffix' => env('ELAF_LOGO_SUFFIX', 'السودان'),
        'motto' => env('ELAF_MOTTO', 'كلمة تبني وطنا'),
        'description' => env('ELAF_DESCRIPTION', 'صحيفة للاقتصاد السياسي السوداني ومحيطه'),
        'masthead' => [
            'editor_in_chief' => env('ELAF_MASTHEAD_EDITOR_IN_CHIEF'),
            'editorial_advisor' => env('ELAF_MASTHEAD_EDITORIAL_ADVISOR'),
            'publisher' => env('ELAF_MASTHEAD_PUBLISHER'),
        ],
        'email' => env('ELAF_CONTACT_EMAIL'),
        'social' => [
            'facebook' => env('ELAF_FACEBOOK_URL'),
            'whatsapp' => env('ELAF_WHATSAPP_CHANNEL_URL'),
            'youtube' => env('ELAF_YOUTUBE_URL'),
            'x' => env('ELAF_X_URL'),
        ],
        // Footer links; pages arrive with FR-PAG-01 (Phase 5).
        'footer_links' => [
            ['label' => 'عن الصحيفة', 'url' => '/page/about'],
            ['label' => 'اتصل بنا', 'url' => '/page/contact'],
            ['label' => 'سياسة الخصوصية', 'url' => '/page/privacy'],
        ],
    ],

    /*
     * Sections of the printed paper (articles.print_section). The website's
     * own sections are the categories.
     */
    'print_sections' => [
        'أخبار', 'أخبار الأسبوع', 'أسواق', 'تقارير', 'ما وراء الأخبار', 'تحقيق',
        'حوار', 'رأي اقتصادي', 'رأي سياسي', 'من صحافة العالم', 'بريد القراء', 'أخيرة',
    ],

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
