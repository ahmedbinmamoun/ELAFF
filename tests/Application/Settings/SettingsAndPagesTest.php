<?php

declare(strict_types=1);

use Elaf\Settings\Application\Page\CreatePage\CreatePageCommand;
use Elaf\Settings\Application\Page\CreatePage\CreatePageHandler;
use Elaf\Settings\Application\Page\DeletePage\DeletePageCommand;
use Elaf\Settings\Application\Page\DeletePage\DeletePageHandler;
use Elaf\Settings\Application\Page\PageAccess;
use Elaf\Settings\Application\Page\PageInput;
use Elaf\Settings\Application\Page\PageInputValidator;
use Elaf\Settings\Application\Page\UpdatePage\UpdatePageCommand;
use Elaf\Settings\Application\Page\UpdatePage\UpdatePageHandler;
use Elaf\Settings\Application\SettingsPermission;
use Elaf\Settings\Application\Site\SiteSettings;
use Elaf\Settings\Application\Site\UpdateSettings\UpdateSettingsCommand;
use Elaf\Settings\Application\Site\UpdateSettings\UpdateSettingsHandler;
use Elaf\Settings\Domain\Events\PageChanged;
use Elaf\Settings\Domain\Events\SettingsChanged;
use Elaf\Settings\Domain\Page\PageId;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Elaf\Shared\Application\Validation\RecordNotFound;
use Elaf\Shared\Infrastructure\Html\HtmlPurifierSanitizer;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAuthorizer;
use Tests\Support\Fakes\InMemoryPageRepository;
use Tests\Support\Fakes\InMemorySettings;
use Tests\Support\Fakes\RecordingEventDispatcher;

beforeEach(function (): void {
    $this->authorizer = new InMemoryAuthorizer([SettingsPermission::ManageSettings->value, SettingsPermission::ManagePages->value]);
    $this->events = new RecordingEventDispatcher;
    $this->clock = new FrozenClock;
    $this->settings = new InMemorySettings(new SiteSettings('إيلاف', motto: 'شعار افتراضي'));
    $this->update = fn (SiteSettings $s) => (new UpdateSettingsHandler($this->authorizer, $this->settings, $this->settings, new ImmediateTransaction, $this->events, $this->clock))
        ->handle(new UpdateSettingsCommand(1, $s));

    $this->pages = new InMemoryPageRepository;
    $access = new PageAccess($this->authorizer);
    $validator = new PageInputValidator($this->pages, new HtmlPurifierSanitizer);
    $this->create = fn (PageInput $p) => (new CreatePageHandler($access, $validator, $this->pages, $this->events, $this->clock))->handle(new CreatePageCommand($p));
    $this->edit = fn (int $id, PageInput $p) => (new UpdatePageHandler($access, $validator, $this->pages, $this->events, $this->clock))->handle(new UpdatePageCommand($id, $p));
    $this->delete = fn (int $id) => (new DeletePageHandler($access, $this->pages, $this->events, $this->clock))->handle(new DeletePageCommand($id));
    $this->reason = static function (callable $work): string {
        try {
            $work();
        } catch (InvalidInput $e) {
            return $e->field.':'.$e->reason;
        }

        return 'none';
    };
});

it('stores only the settings that changed and announces them (FR-SET-01, 02)', function (): void {
    ($this->update)(new SiteSettings('إيلاف', motto: 'شعار افتراضي', contactEmail: ' info@elaf.example ', editorsCanPublishContributorArticles: true));

    expect($this->settings->stored)->toBe(['contact_email' => 'info@elaf.example', 'contributors_editor_can_publish' => true])
        ->and($this->settings->site()->contactEmail)->toBe('info@elaf.example')
        ->and($this->events->ofType(SettingsChanged::class)[0]->changedKeys ?? null)->toBe(['contact_email', 'contributors_editor_can_publish']);

    ($this->update)($this->settings->site());
    expect($this->events->ofType(SettingsChanged::class))->toHaveCount(1);
});

it('lets an admin clear a default', function (): void {
    ($this->update)(new SiteSettings('إيلاف', motto: null));

    expect($this->settings->site()->motto)->toBeNull();
});

it('checks every settings field', function (SiteSettings $settings, string $expected): void {
    expect(($this->reason)(fn () => ($this->update)($settings)))->toBe($expected);
})->with([
    'name required' => [new SiteSettings('  '), 'name:required'],
    'email' => [new SiteSettings('إيلاف', contactEmail: 'not-an-email'), 'contact_email:invalid_email'],
    'phone' => [new SiteSettings('إيلاف', phone: 'call me'), 'phone:invalid_phone'],
    'social url' => [new SiteSettings('إيلاف', facebookUrl: 'javascript:alert(1)'), 'facebook_url:invalid_url'],
    'analytics' => [new SiteSettings('إيلاف', analyticsId: 'UA-123<script>'), 'analytics_id:invalid_analytics_id'],
    'path outside disk' => [new SiteSettings('إيلاف', logoPath: '../.env'), 'logo_path:invalid_path'],
    'long motto' => [new SiteSettings('إيلاف', motto: str_repeat('ع', 121)), 'motto:too_long'],
    'valid' => [new SiteSettings('إيلاف', contactEmail: 'a@b.sd', phone: '+249 912 345 678', analyticsId: 'G-ABC123XYZ', logoPath: 'settings/logo/a.png'), 'none'],
]);

it('keeps settings for admins (PRD §2)', function (): void {
    $this->authorizer->revoke(SettingsPermission::ManageSettings->value);

    expect(fn () => ($this->update)(new SiteSettings('اسم آخر')))->toThrow(NotAuthorized::class);
});

it('creates, edits and deletes static pages with a sanitized body (FR-PAG-01)', function (): void {
    $id = ($this->create)(new PageInput('من نحن', body: '<p>نص</p><script>alert(1)</script>'));
    $page = $this->pages->find($id);

    expect($page?->data->slug->value)->toBe('من-نحن')
        ->and($page?->data->body)->toBe('<p>نص</p>');

    ($this->edit)($id->value, new PageInput('من نحن', 'about', '<p>نص جديد</p>', false));
    expect($this->pages->find($id)?->data->slug->value)->toBe('about')
        ->and($this->pages->find($id)?->data->isPublished)->toBeFalse();

    ($this->delete)($id->value);
    expect($this->pages->find($id))->toBeNull()
        ->and($this->events->ofType(PageChanged::class))->toHaveCount(3)
        ->and(fn () => ($this->delete)($id->value))->toThrow(RecordNotFound::class);
});

it('keeps page slugs unique and needs the pages permission', function (): void {
    $id = ($this->create)(new PageInput('اتصل بنا', 'contact', '<p>.</p>'));

    expect(($this->reason)(fn () => ($this->create)(new PageInput('تواصل', 'contact', '<p>.</p>'))))->toBe('slug:slug_taken')
        ->and(($this->reason)(fn () => ($this->edit)($id->value, new PageInput('اتصل بنا', 'contact', '<p>تعديل</p>'))))->toBe('none')
        ->and(($this->reason)(fn () => ($this->create)(new PageInput(' '))))->toBe('title:required');

    $this->authorizer->revoke(SettingsPermission::ManagePages->value);
    expect(fn () => ($this->create)(new PageInput('صفحة')))->toThrow(NotAuthorized::class)
        ->and(fn () => ($this->delete)(PageId::fromInt($id->value)->value))->toThrow(NotAuthorized::class);
});
