<?php

declare(strict_types=1);

use App\Models\User;
use Elaf\Engagement\Domain\Letter\Exception\LetterNotFound;
use Elaf\Engagement\Domain\Letter\LetterStatus;
use Elaf\Engagement\Domain\Letter\ReaderLetter;
use Elaf\Engagement\Domain\Letter\ReaderLetterId;
use Elaf\Engagement\Domain\Letter\ReaderLetterRepository;
use Elaf\Engagement\Domain\Letter\Sender;
use Elaf\Engagement\Infrastructure\Persistence\EloquentReaderLetterRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\InMemoryReaderLetters;

uses(RefreshDatabase::class);

dataset('letter repositories', [
    'in-memory' => fn (): ReaderLetterRepository => new InMemoryReaderLetters,
    'eloquent' => fn (): ReaderLetterRepository => new EloquentReaderLetterRepository(app(ConnectionInterface::class)),
]);

it('round-trips a letter through moderation', function (ReaderLetterRepository $letters): void {
    $clock = new FrozenClock('2026-10-10 09:15:00');
    $moderator = User::factory()->create()->id;
    $letter = ReaderLetter::submit($letters->nextId(), new Sender('قارئ', 'reader@example.com', '0912345678'), 'عنوان', "نص الرسالة التجريبية\nسطر ثانٍ", str_repeat('c', 64), $clock);
    $letters->save($letter);

    $loaded = $letters->get($letter->id());
    expect($loaded->sender())->toEqual(new Sender('قارئ', 'reader@example.com', '0912345678'))
        ->and($loaded->body())->toBe("نص الرسالة التجريبية\nسطر ثانٍ")
        ->and($loaded->receivedAt()->getTimestamp())->toBe($clock->now()->getTimestamp())
        ->and($loaded->releaseEvents())->toBe([]);

    $loaded->reject($moderator, 'مكررة', $clock);
    $letters->save($loaded);
    expect($letters->get($letter->id())->status())->toBe(LetterStatus::Rejected)
        ->and($letters->get($letter->id())->moderatedBy())->toBe($moderator)
        ->and(fn () => $letters->get(ReaderLetterId::fromInt(9999)))->toThrow(LetterNotFound::class);
})->with('letter repositories');
