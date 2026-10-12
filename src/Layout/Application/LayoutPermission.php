<?php

declare(strict_types=1);

namespace Elaf\Layout\Application;

/**
 * PRD §2: homepage, menus and ads belong to the Editor-in-Chief and Admin;
 * unsanitized ad HTML to the Admin only (FR-ADS-02).
 */
enum LayoutPermission: string
{
    case ManageHomepage = 'homepage.manage';
    case ManageMenus = 'menus.manage';
    case ManageAds = 'ads.manage';
    case RawAdHtml = 'ads.raw-html';
}
