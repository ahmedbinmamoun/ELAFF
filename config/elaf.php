<?php

declare(strict_types=1);

/*
 * Installation defaults. Site identity and the contributor switches are
 * edited in the admin settings (FR-SET-01) and stored in the database.
 */
return [
    /*
     * Default site identity for a fresh installation. The admin's settings
     * page (FR-SET-01) overrides every value; empty values are not rendered.
     */
    'site' => [
        'name' => env('ELAF_SITE_NAME', 'إيلاف'),
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
    ],

    /*
     * Sections of the printed paper (articles.print_section). The website's
     * own sections are the categories.
     */
    'print_sections' => [
        'أخبار', 'أخبار الأسبوع', 'أسواق', 'تقارير', 'ما وراء الأخبار', 'تحقيق',
        'حوار', 'رأي اقتصادي', 'رأي سياسي', 'من صحافة العالم', 'بريد القراء', 'أخيرة',
    ],

    /*
     * Print issues (PRD §11). PDFs and page images are public files.
     */
    'issues' => [
        'disk' => env('ELAF_ISSUES_DISK', 'public'),
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
