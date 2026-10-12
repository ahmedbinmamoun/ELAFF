<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Factories\Support\ArabicText;
use Elaf\Settings\Application\Page\PageData;
use Elaf\Settings\Application\Page\PageRepository;
use Elaf\Shared\Domain\Text\Slug;
use Illuminate\Database\Seeder;

/**
 * Local demo static pages (FR-PAG-01) with generated text: the real texts
 * come from the newspaper. Skips pages that exist.
 */
final class DemoPagesSeeder extends Seeder
{
    public const PAGES = [
        'about' => 'عن الصحيفة',
        'contact' => 'اتصل بنا',
        'advertise' => 'أعلن معنا',
        'privacy' => 'سياسة الخصوصية',
    ];

    public function __construct(private readonly PageRepository $pages) {}

    public function run(): void
    {
        $text = new ArabicText;

        foreach (self::PAGES as $slug => $title) {
            if ($this->pages->slugExists(Slug::fromString($slug))) {
                continue;
            }

            $this->pages->create(new PageData($title, Slug::fromString($slug), $text->bodyHtml(4), true));
        }
    }
}
