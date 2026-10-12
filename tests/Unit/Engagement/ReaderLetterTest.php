<?php

declare(strict_types=1);

use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Engagement\Domain\Letter\Events\ReaderLetterApproved;
use Elaf\Engagement\Domain\Letter\Events\ReaderLetterConverted;
use Elaf\Engagement\Domain\Letter\Events\ReaderLetterReceived;
use Elaf\Engagement\Domain\Letter\Events\ReaderLetterRejected;
use Elaf\Engagement\Domain\Letter\Exception\InvalidLetter;
use Elaf\Engagement\Domain\Letter\Exception\LetterWorkflowViolation;
use Elaf\Engagement\Domain\Letter\LetterStatus;
use Elaf\Engagement\Domain\Letter\ReaderLetter;
use Elaf\Engagement\Domain\Letter\ReaderLetterId;
use Elaf\Engagement\Domain\Letter\Sender;
use Tests\Support\Fakes\FrozenClock;

beforeEach(function (): void {
    $this->clock = new FrozenClock;
    $this->letter = fn (string $title = 'عن الكهرباء', string $body = 'نص رسالة تجريبية طويل بما يكفي للاختبار.'): ReaderLetter => ReaderLetter::submit(
        ReaderLetterId::fromInt(1), new Sender('قارئ', 'reader@example.com', '+249 900 000 000'), $title, $body, str_repeat('a', 64), $this->clock,
    );
});

it('arrives pending (FR-LET-01, 02)', function (): void {
    $letter = ($this->letter)('  عنوان  ', "سطر أول\r\nسطر ثانٍ من الرسالة التجريبية");

    expect($letter->status())->toBe(LetterStatus::Pending)
        ->and($letter->title())->toBe('عنوان')
        ->and($letter->body())->toBe("سطر أول\nسطر ثانٍ من الرسالة التجريبية")
        ->and($letter->releaseEvents())->sequence(fn ($e) => $e->toBeInstanceOf(ReaderLetterReceived::class));
});

it('is approved then converted once', function (): void {
    $letter = ($this->letter)();
    $letter->approve(3, $this->clock);
    $letter->markConverted(ArticleId::fromInt(9), 3, $this->clock);

    expect($letter->articleId()?->value)->toBe(9)
        ->and(array_map(get_class(...), $letter->releaseEvents()))->toBe([ReaderLetterReceived::class, ReaderLetterApproved::class, ReaderLetterConverted::class])
        ->and(fn () => $letter->markConverted(ArticleId::fromInt(10), 3, $this->clock))->toThrow(LetterWorkflowViolation::class);
});

it('is rejected only with a reason, and moderated only once', function (): void {
    $letter = ($this->letter)();

    expect(fn () => $letter->reject(3, '  ', $this->clock))->toThrow(LetterWorkflowViolation::class);
    $letter->reject(3, 'خارج الموضوع', $this->clock);

    expect($letter->status())->toBe(LetterStatus::Rejected)
        ->and($letter->moderatorNote())->toBe('خارج الموضوع')
        ->and($letter->releaseEvents()[1] ?? null)->toBeInstanceOf(ReaderLetterRejected::class)
        ->and(fn () => $letter->approve(3, $this->clock))->toThrow(LetterWorkflowViolation::class)
        ->and(fn () => $letter->markConverted(ArticleId::fromInt(1), 3, $this->clock))->toThrow(LetterWorkflowViolation::class);
});

it('checks the sender and the text', function (Closure $make, string $field, string $reason): void {
    try {
        $make($this);
        $this->fail('No exception.');
    } catch (InvalidLetter $e) {
        expect([$e->field, $e->reason])->toBe([$field, $reason]);
    }
})->with([
    'no name' => [fn () => new Sender(' ', 'a@b.sd'), 'name', 'letter_name'],
    'bad email' => [fn () => new Sender('قارئ', 'not-mail'), 'email', 'invalid_email'],
    'bad phone' => [fn () => new Sender('قارئ', 'a@b.sd', 'call me'), 'phone', 'invalid_phone'],
    'no title' => [fn ($t) => ($t->letter)(' '), 'title', 'letter_title'],
    'short body' => [fn ($t) => ($t->letter)('عنوان', 'قصير'), 'body', 'letter_body'],
    'long body' => [fn ($t) => ($t->letter)('عنوان', str_repeat('ب', 5001)), 'body', 'letter_body'],
]);
