<?php

declare(strict_types=1);

namespace Elaf\Shared\Presentation;

/**
 * Inline SVG icons (24×24, stroke) for the public site and the section icons
 * editors pick in the admin. Keys are stable: they are stored in
 * categories.icon. Adding an icon = adding an entry.
 */
final class Icons
{
    /** Icons offered for sections (categories), with their Arabic label. */
    public const SECTION_ICONS = [
        'map' => 'خريطة',
        'chart' => 'رسم بياني',
        'flame' => 'شعلة',
        'landmark' => 'مبنى حكومي',
        'globe' => 'كرة أرضية',
        'document' => 'مستند',
        'pen' => 'قلم',
        'book' => 'كتاب',
        'grid' => 'شبكة',
        'newspaper' => 'صحيفة',
        'briefcase' => 'حقيبة',
        'users' => 'أشخاص',
    ];

    private const PATHS = [
        'map' => 'M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2Zm0 0v14m6-12v14',
        'chart' => 'M4 20h16M7 16v-5m5 5V8m5 8v-9',
        'flame' => 'M12 3c1 3 4 5 4 9a4 4 0 1 1-8 0c0-2 1-3 2-4 0 2 1 3 2 3 0-3-1-5 0-8Z',
        'landmark' => 'M3 10h18M5 10v8m4-8v8m6-8v8m4-8v8M3 21h18M12 3 3 8h18l-9-5Z',
        'globe' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-9-9h18M12 3c2.5 2.5 3.5 5.5 3.5 9S14.5 18.5 12 21c-2.5-2.5-3.5-5.5-3.5-9S9.5 5.5 12 3Z',
        'document' => 'M14 3H6v18h12V7l-4-4Zm0 0v4h4M9 12h6m-6 4h6',
        'pen' => 'm4 20 4-1 11-11-3-3L5 16l-1 4Zm10-13 3 3',
        'book' => 'M4 5a2 2 0 0 1 2-2h13v16H6a2 2 0 0 0-2 2V5Zm0 16a2 2 0 0 1 2-2h13',
        'grid' => 'M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z',
        'newspaper' => 'M4 5h13v14H6a2 2 0 0 1-2-2V5Zm13 4h3v8a2 2 0 0 1-2 2M7 9h7m-7 4h7m-7 4h4',
        'briefcase' => 'M4 8h16v11H4V8Zm5 0V5h6v3M4 13h16',
        'users' => 'M16 19v-1a4 4 0 0 0-8 0v1m4-8a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm6 8v-1a3 3 0 0 0-2-3m-12 4v-1a3 3 0 0 1 2-3',
        'menu' => 'M4 6h16M4 12h16M4 18h16',
        'search' => 'm20 20-4-4m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z',
        'close' => 'M6 6l12 12M18 6 6 18',
        'home' => 'M4 11 12 4l8 7v9h-5v-6H9v6H4v-9Z',
        'heart' => 'M12 20s-7-4.5-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.5-7 10-7 10Z',
        'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0',
        'clock' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-13v4l3 2',
        'calendar' => 'M5 6h14v14H5V6Zm0 4h14M9 3v4m6-4v4',
        'chevron-left' => 'm15 6-6 6 6 6',
        'chevron-right' => 'm9 6 6 6-6 6',
        'chevron-down' => 'm6 9 6 6 6-6',
        'mail' => 'M4 6h16v12H4V6Zm0 0 8 7 8-7',
        'bolt' => 'M13 3 5 14h6l-1 7 8-11h-6l1-7Z',
        'trending' => 'M4 16 10 10l4 4 6-6m0 0h-5m5 0v5',
    ];

    /** Brand marks (filled). */
    private const FILLED = [
        'facebook' => 'M14 8h2V5h-2.5C11 5 10 6.6 10 9v2H8v3h2v7h3v-7h2.4l.6-3h-3V9.2c0-.8.2-1.2 1-1.2Z',
        'whatsapp' => 'M12 3a9 9 0 0 0-7.8 13.4L3 21l4.7-1.2A9 9 0 1 0 12 3Zm4.6 12.6c-.2.6-1.2 1.1-1.7 1.2-.4.1-1 .1-1.6-.1-3-1-5-4-5.2-4.2-.2-.2-1.2-1.6-1.2-3s.8-2.1 1-2.4c.3-.3.6-.3.8-.3h.6c.2 0 .4 0 .6.5l.8 1.9c.1.2.1.4 0 .6l-.4.6-.4.4c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.8-1c.2-.3.4-.2.6-.1l1.8.9c.3.1.4.2.5.3.1.2.1.8-.2 1.4Z',
        'youtube' => 'M21.6 8.2a2.5 2.5 0 0 0-1.8-1.8C18.3 6 12 6 12 6s-6.3 0-7.8.4A2.5 2.5 0 0 0 2.4 8.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 3.8 2.5 2.5 0 0 0 1.8 1.8C5.7 18 12 18 12 18s6.3 0 7.8-.4a2.5 2.5 0 0 0 1.8-1.8A26 26 0 0 0 22 12a26 26 0 0 0-.4-3.8ZM10 15V9l5.2 3L10 15Z',
        'x' => 'M17.8 4h2.8l-6.1 7 7.2 9h-5.6l-4.4-5.7L6.6 20H3.8l6.5-7.5L3.4 4h5.7l4 5.2L17.8 4Zm-1 14.4h1.6L8.3 5.5H6.6l10.2 12.9Z',
    ];

    public static function svg(string $name, string $class = 'h-5 w-5'): string
    {
        $filled = self::FILLED[$name] ?? null;
        $path = $filled ?? self::PATHS[$name] ?? self::PATHS['grid'];
        $paint = $filled !== null
            ? 'fill="currentColor"'
            : 'fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"';

        return '<svg class="'.e($class).'" viewBox="0 0 24 24" '.$paint.' aria-hidden="true" focusable="false"><path d="'.$path.'"/></svg>';
    }

    public static function has(string $name): bool
    {
        return isset(self::PATHS[$name]) || isset(self::FILLED[$name]);
    }
}
