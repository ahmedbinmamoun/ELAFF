<?php

declare(strict_types=1);

use Database\Seeders\CategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Engagement\Infrastructure\Persistence\ReaderLetterModel;
use Elaf\Engagement\Presentation\Filament\Letters\Pages\ViewReaderLetter;
use Elaf\Identity\Application\Role;
use Elaf\Shared\Application\Captcha\CaptchaVerifier;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\AdminFixtures;
use Tests\Support\Fakes\FakeCaptchaVerifier;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed([RolesAndPermissionsSeeder::class, CategorySeeder::class]);
    Filament::setCurrentPanel('admin');
    $this->letter = static fn (array $o = []): array => [
        'name' => 'قارئ تجريبي', 'email' => 'reader@example.com', 'phone' => '0912345678',
        'title' => 'رسالة عن الكهرباء', 'body' => "الفقرة الأولى من رسالة تجريبية.\nالفقرة الثانية.", ...$o,
    ];
});

it('takes a letter from the public form (FR-LET-01)', function (): void {
    $this->get('/letters')->assertOk()->assertSee('بريد القراء')->assertSee('name="website"', false);

    $this->post('/letters', ($this->letter)())->assertRedirect('/letters');
    $this->get('/letters')->assertSee('وصلت رسالتك');

    $letter = ReaderLetterModel::query()->firstOrFail();
    expect($letter->status)->toBe('pending')
        ->and($letter->email)->toBe('reader@example.com')
        ->and($letter->ip_hash)->toHaveLength(64)
        ->and($letter->ip_hash)->not->toContain('127.0.0.1');
});

it('drops bots that fill the honeypot, silently', function (): void {
    $this->post('/letters', ($this->letter)(['website' => 'https://spam.example']))->assertRedirect('/letters');

    expect(ReaderLetterModel::query()->count())->toBe(0);
});

it('shows field errors and keeps the input', function (): void {
    $this->from('/letters')->post('/letters', ($this->letter)(['email' => 'not-mail']))
        ->assertRedirect('/letters')
        ->assertSessionHasErrors(['email']);

    $this->from('/letters')->post('/letters', ($this->letter)(['body' => 'قصير']))->assertSessionHasErrors(['body']);
    expect(ReaderLetterModel::query()->count())->toBe(0);
});

it('checks the captcha when one is configured', function (): void {
    $this->app->instance(CaptchaVerifier::class, new FakeCaptchaVerifier);

    $this->from('/letters')->post('/letters', ($this->letter)())->assertSessionHasErrors(['captcha']);
    $this->post('/letters', ($this->letter)(['cf-turnstile-response' => FakeCaptchaVerifier::VALID_TOKEN]))->assertSessionHasNoErrors();

    expect(ReaderLetterModel::query()->count())->toBe(1);
});

it('rate-limits the form', function (): void {
    foreach (range(1, 5) as $i) {
        $this->post('/letters', ($this->letter)(['title' => "رسالة {$i}"]))->assertRedirect();
    }

    $this->post('/letters', ($this->letter)())->assertStatus(429);
});

it('lets an editor approve a letter and turn it into a draft in بريد القراء (FR-LET-02)', function (): void {
    $this->post('/letters', ($this->letter)());
    $letter = ReaderLetterModel::query()->firstOrFail();
    $editor = AdminFixtures::user(Role::Editor);
    $this->actingAs($editor);

    $this->get('/admin/reader-letters')->assertOk()->assertSee('رسالة عن الكهرباء');
    Livewire::test(ViewReaderLetter::class, ['record' => $letter->getRouteKey()])
        ->assertSee('reader@example.com')
        ->assertActionHidden('convert')
        ->callAction('approve')
        ->assertActionHidden('approve')
        ->callAction('convert');

    $article = ArticleModel::query()->with('category')->findOrFail($letter->refresh()->article_id);
    expect($letter->status)->toBe('approved')
        ->and($article->status)->toBe('draft')
        ->and($article->category->slug)->toBe('بريد-القراء')
        ->and($article->title)->toBe('رسالة عن الكهرباء')
        ->and($article->body)->toContain('<p>الفقرة الأولى من رسالة تجريبية.</p>')->toContain('قارئ تجريبي')
        ->and($article->body)->not->toContain('reader@example.com')
        ->and($article->body)->not->toContain('0912345678')
        ->and($article->created_by)->toBe($editor->id);
});

it('rejects with a reason', function (): void {
    $this->post('/letters', ($this->letter)());
    $letter = ReaderLetterModel::query()->firstOrFail();
    $this->actingAs(AdminFixtures::user(Role::Editor));

    Livewire::test(ViewReaderLetter::class, ['record' => $letter->getRouteKey()])
        ->callAction('reject', ['reason' => 'خارج نطاق الصحيفة']);

    expect($letter->refresh()->status)->toBe('rejected')->and($letter->moderator_note)->toBe('خارج نطاق الصحيفة');
});

it('keeps the queue away from writers (PRD §2)', function (): void {
    $this->actingAs(AdminFixtures::user(Role::Writer))->get('/admin/reader-letters')->assertForbidden();
});
