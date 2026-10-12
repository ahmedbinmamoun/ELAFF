<?php

declare(strict_types=1);

use Elaf\Engagement\Application\EngagementPermission;
use Elaf\Engagement\Application\Letter\Approve\ApproveReaderLetterCommand;
use Elaf\Engagement\Application\Letter\Approve\ApproveReaderLetterHandler;
use Elaf\Engagement\Application\Letter\Convert\ConvertLetterToArticleCommand;
use Elaf\Engagement\Application\Letter\Convert\ConvertLetterToArticleHandler;
use Elaf\Engagement\Application\Letter\LetterModeration;
use Elaf\Engagement\Application\Letter\Reject\RejectReaderLetterCommand;
use Elaf\Engagement\Application\Letter\Reject\RejectReaderLetterHandler;
use Elaf\Engagement\Application\Letter\Submit\SubmitReaderLetterCommand;
use Elaf\Engagement\Application\Letter\Submit\SubmitReaderLetterHandler;
use Elaf\Engagement\Domain\Letter\Events\ReaderLetterReceived;
use Elaf\Engagement\Domain\Letter\Exception\LetterWorkflowViolation;
use Elaf\Engagement\Domain\Letter\LetterStatus;
use Elaf\Engagement\Domain\Letter\ReaderLetterId;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Tests\Support\Fakes\FakeCaptchaVerifier;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\ImmediateTransaction;
use Tests\Support\Fakes\InMemoryAuthorizer;
use Tests\Support\Fakes\InMemoryReaderLetters;
use Tests\Support\Fakes\RecordingEventDispatcher;

beforeEach(function (): void {
    $this->letters = new InMemoryReaderLetters;
    $this->authorizer = new InMemoryAuthorizer([EngagementPermission::ModerateLetters->value]);
    $this->events = new RecordingEventDispatcher;
    $clock = new FrozenClock;
    $moderation = new LetterModeration($this->letters, $this->authorizer, new ImmediateTransaction, $this->events);
    $this->submit = fn (array $o = []) => (new SubmitReaderLetterHandler($moderation, new FakeCaptchaVerifier, $clock))->handle(new SubmitReaderLetterCommand(...[
        'name' => 'قارئ تجريبي', 'email' => 'reader@example.com', 'phone' => '', 'title' => 'عن الكهرباء',
        'body' => "الفقرة الأولى من الرسالة.\n\nالفقرة الثانية من الرسالة.", 'ipHash' => str_repeat('f', 64),
        'captchaToken' => FakeCaptchaVerifier::VALID_TOKEN, ...$o,
    ]))->value;
    $this->approve = fn (int $id) => (new ApproveReaderLetterHandler($moderation, $clock))->handle(new ApproveReaderLetterCommand(4, $id));
    $this->reject = fn (int $id, string $reason) => (new RejectReaderLetterHandler($moderation, $clock))->handle(new RejectReaderLetterCommand(4, $id, $reason));
    $this->convert = fn (int $id) => (new ConvertLetterToArticleHandler($moderation, $this->letters, $clock))->handle(new ConvertLetterToArticleCommand(4, $id));
    $this->reason = static function (callable $work): string {
        try {
            $work();
        } catch (InvalidInput $e) {
            return $e->field.':'.$e->reason;
        }

        return 'none';
    };
});

it('queues a letter after the captcha (FR-LET-01)', function (): void {
    $id = ($this->submit)();

    expect($this->letters->get(ReaderLetterId::fromInt($id))->status())->toBe(LetterStatus::Pending)
        ->and($this->letters->get(ReaderLetterId::fromInt($id))->sender()->phone)->toBeNull()
        ->and($this->events->ofType(ReaderLetterReceived::class))->toHaveCount(1)
        ->and(($this->reason)(fn () => ($this->submit)(['captchaToken' => 'bot'])))->toBe('captcha:captcha_failed')
        ->and(($this->reason)(fn () => ($this->submit)(['email' => 'nope'])))->toBe('email:invalid_email')
        ->and(($this->reason)(fn () => ($this->submit)(['body' => 'قصير'])))->toBe('body:letter_body');
});

it('approves, rejects and converts approved letters into drafts (FR-LET-02)', function (): void {
    $kept = ($this->submit)();
    $dropped = ($this->submit)();
    ($this->approve)($kept);
    ($this->reject)($dropped, 'مكررة');

    $article = ($this->convert)($kept);

    expect($article->value)->toBe(501)
        ->and($this->letters->get(ReaderLetterId::fromInt($kept))->articleId()?->value)->toBe(501)
        ->and($this->letters->drafts[0]['draft']->paragraphs)->toBe(['الفقرة الأولى من الرسالة.', 'الفقرة الثانية من الرسالة.'])
        ->and($this->letters->drafts[0]['draft']->senderName)->toBe('قارئ تجريبي')
        ->and($this->letters->drafts[0]['actor'])->toBe(4)
        ->and(fn () => ($this->convert)($kept))->toThrow(LetterWorkflowViolation::class)
        ->and(fn () => ($this->convert)($dropped))->toThrow(LetterWorkflowViolation::class)
        ->and($this->letters->drafts)->toHaveCount(1);
});

it('keeps moderation for editors and above (PRD §2)', function (): void {
    $id = ($this->submit)();
    $this->authorizer->revoke(EngagementPermission::ModerateLetters->value);

    expect(fn () => ($this->approve)($id))->toThrow(NotAuthorized::class)
        ->and(fn () => ($this->reject)($id, 'x'))->toThrow(NotAuthorized::class)
        ->and(fn () => ($this->convert)($id))->toThrow(NotAuthorized::class);
});
