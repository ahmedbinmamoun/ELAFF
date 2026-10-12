<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Database\Factories\Support\DemoPdf;
use DateTimeImmutable;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Domain\Article\ArticleRepository;
use Elaf\Content\Domain\Article\ArticleStatus;
use Elaf\Content\Domain\Article\IssuePlacement;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Identity\Application\Role;
use Elaf\Identity\Domain\UserId;
use Elaf\Publishing\Application\Issue\IssueInput;
use Elaf\Publishing\Application\Issue\IssueUploader;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Publishing\Infrastructure\Persistence\IssueModel;
use Elaf\Shared\Domain\Time\Clock;
use Illuminate\Database\Seeder;

/**
 * Local demo print issues: generated PDFs (coloured blocks, no real pages)
 * uploaded like the admin does, so the queue renders them. Some published
 * demo articles are linked to the latest issue. Runs once.
 */
final class DemoIssuesSeeder extends Seeder
{
    public const ISSUES = 6;

    public const PAGES = 4;

    public const LATEST_NUMBER = 120;

    public function __construct(
        private readonly IssueUploader $uploader,
        private readonly ArticleRepository $articles,
        private readonly Clock $clock,
    ) {}

    public function run(): void
    {
        if (IssueModel::query()->exists()) {
            return;
        }

        // The weekly paper comes out on Thursday; the newest issue gets the articles.
        $thursday = $this->clock->now()->modify('thursday this week');
        $thursday = $thursday > $this->clock->now() ? $thursday->modify('-1 week') : $thursday;

        for ($weeksAgo = self::ISSUES - 1; $weeksAgo > 0; $weeksAgo--) {
            $this->upload(self::LATEST_NUMBER - $weeksAgo, $thursday->modify(sprintf('-%d weeks', $weeksAgo)));
        }

        $this->linkArticles($this->upload(self::LATEST_NUMBER, $thursday));
    }

    private function upload(int $number, DateTimeImmutable $date): IssueId
    {
        $file = (string) tempnam(sys_get_temp_dir(), 'elaf-demo-issue-');
        file_put_contents($file, DemoPdf::newspaper($number, self::PAGES));

        try {
            return $this->uploader->upload(new IssueInput($number, $date->format('Y-m-d')), $file, null);
        } finally {
            @unlink($file);
        }
    }

    private function linkArticles(IssueId $issue): void
    {
        $chief = User::query()->where('email', Role::EditorInChief->value.'@elaf.test')->value('id');

        if (! is_numeric($chief)) {
            return;
        }

        $ids = ArticleModel::query()->where('status', ArticleStatus::Published->value)->orderByDesc('published_at')->limit(8)->pluck('id');

        foreach ($ids->values() as $position => $id) {
            if (! is_numeric($id)) {
                continue;
            }

            $article = $this->articles->get(ArticleId::fromInt((int) $id));
            $article->assignToIssue(
                new IssuePlacement($issue, 1 + intdiv((int) $position, 2)),
                UserId::fromInt((int) $chief),
                $this->clock,
            );
            $article->releaseEvents();
            $this->articles->save($article);
        }
    }
}
