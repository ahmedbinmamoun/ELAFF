<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Database\Factories\Support\ArabicText;
use DateTimeImmutable;
use Elaf\Content\Domain\Article\Article;
use Elaf\Content\Domain\Article\ArticleBody;
use Elaf\Content\Domain\Article\ArticleContent;
use Elaf\Content\Domain\Article\ArticleOrigin;
use Elaf\Content\Domain\Article\ArticleRepository;
use Elaf\Content\Domain\Article\ArticleSource;
use Elaf\Content\Domain\Article\FeaturedImage;
use Elaf\Content\Domain\Article\SeriesPlacement;
use Elaf\Content\Domain\Article\Title;
use Elaf\Content\Domain\Author\AuthorId;
use Elaf\Content\Domain\Category\CategoryId;
use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Content\Domain\Tag\TagId;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Content\Infrastructure\Persistence\Models\AuthorModel;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Content\Infrastructure\Persistence\Models\SeriesModel;
use Elaf\Content\Infrastructure\Persistence\Models\TagModel;
use Elaf\Contributors\Infrastructure\Persistence\ContributorProfileModel;
use Elaf\Identity\Application\Role;
use Elaf\Identity\Domain\UserId;
use Elaf\Shared\Application\Html\HtmlSanitizer;
use Elaf\Shared\Application\Html\SanitizerProfile;
use Elaf\Shared\Domain\Text\Slug;
use Elaf\Shared\Domain\Time\Clock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Local demo data: one user per role (password "password"), authors, tags, a
 * series and articles in every workflow state. Generated text only. Runs
 * once: skipped when articles already exist.
 */
final class DemoContentSeeder extends Seeder
{
    public const PASSWORD = 'password';

    private ArabicText $text;

    public function __construct(
        private readonly ArticleRepository $articles,
        private readonly HtmlSanitizer $sanitizer,
        private readonly Clock $clock,
    ) {
        $this->text = new ArabicText;
    }

    public function run(): void
    {
        $this->contributor();

        if (ArticleModel::withTrashed()->exists()) {
            return;
        }

        $users = $this->users();
        $authors = $this->authors($users[Role::Writer->value]);
        $tags = $this->tags();
        $seriesId = $this->series();
        $images = $this->placeholderImages(8);
        $categories = array_values(CategoryModel::query()->orderBy('sort_order')->get(['id'])->map(static fn (CategoryModel $c): int => $c->id)->all());

        $writer = UserId::fromInt($users[Role::Writer->value]->id);
        $editor = UserId::fromInt($users[Role::Editor->value]->id);
        $chief = UserId::fromInt($users[Role::EditorInChief->value]->id);
        $now = $this->clock->now();

        for ($i = 0; $i < 40; $i++) {
            $createdAt = $now->modify(sprintf('-%d hours', 6 + $i * 9));
            $series = $i < 3 ? new SeriesPlacement(SeriesId::fromInt($seriesId), 3 - $i) : null;
            $article = Article::draft(
                $this->articles->nextId(),
                ArticleOrigin::Staff,
                $writer,
                $this->content($this->text->pick($categories), $this->text->pick($authors), $tags, $series, $images[$i % 8] ?? null),
                $this->clockAt($createdAt),
            );
            $later = $this->clockAt($createdAt->modify('+2 hours'));

            match (true) {
                $i === 35 => null,
                $i === 36 => $article->submitForReview($writer, $later),
                $i === 37 => $this->approve($article, $writer, $editor, $later),
                $i === 38 => $this->returnArticle($article, $writer, $editor, $later),
                $i === 39 => $this->scheduleArticle($article, $writer, $editor, $chief, $later),
                default => $this->publish($article, $writer, $editor, $chief, $later, breaking: $i < 2, featured: $i < 3),
            };

            $article->releaseEvents();
            $this->articles->save($article);
        }
    }

    /**
     * @return array<string, User>
     */
    private function users(): array
    {
        $users = [];

        foreach (Role::staff() as $role) {
            $user = User::query()->firstOrCreate(
                ['email' => $role->value.'@elaf.test'],
                ['name' => $this->text->personName(), 'password' => self::PASSWORD, 'email_verified_at' => $this->clock->now()],
            );
            $user->syncRoles([$role->value]);
            $users[$role->value] = $user;
        }

        return $users;
    }

    /**
     * An approved demo contributor (contributor@elaf.test) with profile,
     * author and role, so the /writer portal can be tried out.
     */
    private function contributor(): void
    {
        $user = User::query()->firstOrNew(['email' => Role::Contributor->value.'@elaf.test']);
        $user->forceFill([
            'name' => $user->exists ? $user->name : 'كاتب ضيف تجريبي',
            'password' => $user->exists ? $user->password : self::PASSWORD,
            'type' => 'contributor',
            'status' => 'active',
            'email_verified_at' => $user->email_verified_at ?? $this->clock->now(),
        ])->save();
        $user->syncRoles([Role::Contributor->value]);

        $author = AuthorModel::query()->firstOrCreate(['user_id' => $user->id], ['name' => $user->name, 'slug' => Slug::fromText('كاتب ضيف تجريبي')->value]);

        ContributorProfileModel::query()->firstOrCreate(['user_id' => $user->id], [
            'author_id' => $author->id,
            'bio' => $this->text->sentence(),
            'terms_accepted_at' => $this->clock->now(),
            'approved_at' => $this->clock->now(),
        ]);
    }

    /**
     * @return list<int>
     */
    private function authors(User $writer): array
    {
        $ids = [];

        for ($i = 0; $i < 8; $i++) {
            $name = $this->text->personName().' '.($i + 1);
            $ids[] = AuthorModel::query()->firstOrCreate(['slug' => Slug::fromText($name)->value], [
                'name' => $name,
                'title' => $i < 3 ? 'كاتب عمود' : 'محرر',
                'bio' => $this->text->sentence(),
                'is_columnist' => $i < 3,
                'user_id' => $i === 0 ? $writer->id : null,
            ])->id;
        }

        return $ids;
    }

    /**
     * @return list<int>
     */
    private function tags(): array
    {
        $ids = [];

        for ($i = 0; $i < 12; $i++) {
            $name = $this->text->words(2);
            $ids[] = TagModel::query()->firstOrCreate(['slug' => Slug::fromText($name)->value], ['name' => $name])->id;
        }

        return array_values(array_unique($ids));
    }

    private function series(): int
    {
        $title = $this->text->title();

        return SeriesModel::query()->firstOrCreate(['slug' => Slug::fromText($title)->value], [
            'title' => $title,
            'description' => $this->text->sentence(),
        ])->id;
    }

    /**
     * @param  list<int>  $tags
     */
    private function content(int $categoryId, int $authorId, array $tags, ?SeriesPlacement $series, ?string $image = null): ArticleContent
    {
        $title = $this->text->title();
        $tagIds = array_values(array_unique([$this->text->pick($tags), $this->text->pick($tags)]));

        return new ArticleContent(
            title: new Title($title),
            slug: Slug::fromText($title),
            body: ArticleBody::fromSanitizedHtml($this->sanitizer->sanitize($this->text->bodyHtml(), SanitizerProfile::Staff)),
            source: new ArticleSource('إيلاف'),
            categoryId: CategoryId::fromInt($categoryId),
            authorIds: [AuthorId::fromInt($authorId)],
            tagIds: array_map(TagId::fromInt(...), $tagIds),
            kicker: $this->text->words(2),
            subtitle: $this->text->sentence(),
            dateline: $this->text->place(),
            excerpt: $this->text->sentence(12, 24),
            series: $series,
            featuredImage: $image === null ? null : new FeaturedImage($image, $this->text->sentence(4, 8), 'إيلاف'),
        );
    }

    private function approve(Article $article, UserId $writer, UserId $editor, Clock $clock): void
    {
        $article->submitForReview($writer, $clock);
        $article->approve($editor, $clock);
    }

    private function returnArticle(Article $article, UserId $writer, UserId $editor, Clock $clock): void
    {
        $article->submitForReview($writer, $clock);
        $article->returnToWriter($editor, $this->text->sentence(), $clock);
    }

    private function scheduleArticle(Article $article, UserId $writer, UserId $editor, UserId $chief, Clock $clock): void
    {
        $this->approve($article, $writer, $editor, $clock);
        $article->schedule($this->clock->now()->modify('+1 day'), $chief, $this->clockAt($this->clock->now()));
    }

    private function publish(Article $article, UserId $writer, UserId $editor, UserId $chief, Clock $clock, bool $breaking, bool $featured): void
    {
        $this->approve($article, $writer, $editor, $clock);
        $article->publish($chief, $clock);

        if ($breaking) {
            $article->markBreaking(null, $chief, $clock);
        }

        if ($featured) {
            $article->promote(true, 10, $chief, $clock);
        }
    }

    /**
     * Abstract gradient pictures for demo articles (no real photos).
     *
     * @return list<string> paths on the public disk
     */
    private function placeholderImages(int $count): array
    {
        if (! function_exists('imagecreatetruecolor')) {
            return [];
        }

        $palettes = [[14, 42, 71], [200, 16, 46], [28, 61, 99], [176, 112, 32], [35, 94, 78], [92, 56, 120], [60, 70, 82], [150, 60, 30]];
        $paths = [];
        Storage::disk('public')->makeDirectory('demo');

        for ($i = 0; $i < $count; $i++) {
            $image = imagecreatetruecolor(1280, 720);
            [$r, $g, $b] = $palettes[$i % count($palettes)];

            for ($y = 0; $y < 720; $y++) {
                $f = $y / 720;
                $shade = static fn (int $c): int => max(0, min(255, (int) ($c + (255 - $c) * $f * 0.6)));
                $color = imagecolorallocate($image, $shade($r), $shade($g), $shade($b));
                imageline($image, 0, $y, 1280, $y, (int) $color);
            }

            $path = "demo/placeholder-{$i}.jpg";
            ob_start();
            imagejpeg($image, null, 80);
            Storage::disk('public')->put($path, (string) ob_get_clean());
            $paths[] = $path;
        }

        return $paths;
    }

    private function clockAt(DateTimeImmutable $moment): Clock
    {
        return new readonly class($moment) implements Clock
        {
            public function __construct(private DateTimeImmutable $moment) {}

            public function now(): DateTimeImmutable
            {
                return $this->moment;
            }
        };
    }
}
