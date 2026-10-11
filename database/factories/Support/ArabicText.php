<?php

declare(strict_types=1);

namespace Database\Factories\Support;

use InvalidArgumentException;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Generated Arabic placeholder text for seeds and tests (PRD §14). Never
 * real articles: these are shuffled common words with no meaning.
 */
final class ArabicText
{
    private const WORDS = [
        'الاقتصاد', 'السوق', 'الحكومة', 'المواطن', 'التنمية', 'المشروع', 'القطاع', 'الإنتاج', 'التجارة', 'الزراعة',
        'الصادرات', 'الواردات', 'الميزانية', 'الاستثمار', 'البنك', 'العملة', 'الأسعار', 'الخدمات', 'الطاقة', 'المياه',
        'التعليم', 'الصحة', 'النقل', 'المدينة', 'الولاية', 'المجلس', 'اللجنة', 'الوزارة', 'الشركة', 'المصنع',
        'في', 'من', 'إلى', 'على', 'عن', 'مع', 'بعد', 'قبل', 'خلال', 'حول',
        'جديد', 'كبير', 'مهم', 'عام', 'محلي', 'دولي', 'سنوي', 'أولي', 'رسمي', 'مستمر',
        'أعلن', 'أكد', 'أشار', 'قال', 'أوضح', 'ناقش', 'بحث', 'اتفق', 'طالب', 'دعا',
        'الأسبوع', 'العام', 'الشهر', 'اليوم', 'المرحلة', 'الخطة', 'البرنامج', 'التقرير', 'الاجتماع', 'المؤتمر',
        'الجهود', 'النتائج', 'الأرقام', 'المؤشرات', 'التحديات', 'الفرص', 'الحلول', 'الإصلاحات', 'السياسات', 'الأهداف',
    ];

    private const NAMES = ['أحمد', 'محمد', 'فاطمة', 'عائشة', 'عمر', 'خالد', 'مريم', 'سارة', 'يوسف', 'إبراهيم', 'آمنة', 'حسن'];

    private const FAMILY = ['عبدالله', 'الطيب', 'إدريس', 'عثمان', 'الأمين', 'بشير', 'صالح', 'النور', 'حامد', 'مصطفى'];

    private const PLACES = ['الخرطوم', 'بورتسودان', 'ود مدني', 'كسلا', 'الأبيض', 'عطبرة', 'نيالا', 'القضارف'];

    private Randomizer $random;

    public function __construct(int $seed = 2026)
    {
        $this->random = new Randomizer(new Mt19937($seed));
    }

    public function words(int $count): string
    {
        $words = [];
        for ($i = 0; $i < $count; $i++) {
            $words[] = self::WORDS[$this->random->getInt(0, count(self::WORDS) - 1)];
        }

        return implode(' ', $words);
    }

    public function sentence(int $min = 6, int $max = 14): string
    {
        return $this->words($this->random->getInt($min, $max)).'.';
    }

    public function title(): string
    {
        return $this->words($this->random->getInt(4, 9));
    }

    public function paragraph(int $sentences = 4): string
    {
        $text = [];
        for ($i = 0; $i < $sentences; $i++) {
            $text[] = $this->sentence();
        }

        return implode(' ', $text);
    }

    /**
     * HTML body with paragraphs and a subheading.
     */
    public function bodyHtml(int $paragraphs = 5): string
    {
        $html = '';
        for ($i = 0; $i < $paragraphs; $i++) {
            if ($i === 2) {
                $html .= '<h2>'.$this->title().'</h2>';
            }
            $html .= '<p>'.$this->paragraph($this->random->getInt(3, 6)).'</p>';
        }

        return $html;
    }

    public function personName(): string
    {
        return $this->pick(self::NAMES).' '.$this->pick(self::FAMILY);
    }

    public function place(): string
    {
        return $this->pick(self::PLACES);
    }

    public function int(int $min, int $max): int
    {
        return $this->random->getInt($min, $max);
    }

    /**
     * @template T
     *
     * @param  list<T>  $items
     * @return T
     */
    public function pick(array $items): mixed
    {
        if ($items === []) {
            throw new InvalidArgumentException('Cannot pick from an empty list.');
        }

        return $items[$this->random->getInt(0, count($items) - 1)];
    }
}
