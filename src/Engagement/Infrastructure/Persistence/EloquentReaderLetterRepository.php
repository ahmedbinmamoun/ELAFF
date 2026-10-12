<?php

declare(strict_types=1);

namespace Elaf\Engagement\Infrastructure\Persistence;

use Carbon\CarbonImmutable;
use Elaf\Content\Domain\Article\ArticleId;
use Elaf\Content\Infrastructure\Persistence\Eloquent\LocalTime;
use Elaf\Engagement\Domain\Letter\Exception\LetterNotFound;
use Elaf\Engagement\Domain\Letter\LetterStatus;
use Elaf\Engagement\Domain\Letter\ReaderLetter;
use Elaf\Engagement\Domain\Letter\ReaderLetterId;
use Elaf\Engagement\Domain\Letter\ReaderLetterRepository;
use Elaf\Engagement\Domain\Letter\Sender;
use Illuminate\Database\ConnectionInterface;

final readonly class EloquentReaderLetterRepository implements ReaderLetterRepository
{
    public function __construct(private ConnectionInterface $db) {}

    public function nextId(): ReaderLetterId
    {
        return ReaderLetterId::fromInt((int) $this->db->table('reader_letter_id_sequence')->insertGetId([]));
    }

    public function get(ReaderLetterId $id): ReaderLetter
    {
        $m = ReaderLetterModel::query()->find($id->value) ?? throw LetterNotFound::withId($id);

        return ReaderLetter::reconstitute(
            $id,
            new Sender($m->name, $m->email, $m->phone),
            $m->title,
            $m->body,
            $m->ip_hash,
            LocalTime::fromColumn($m->received_at) ?? $m->received_at->toDateTimeImmutable(),
            LetterStatus::from($m->status),
            $m->moderator_note,
            $m->moderated_by,
            $m->article_id === null ? null : ArticleId::fromInt($m->article_id),
        );
    }

    public function save(ReaderLetter $letter): void
    {
        $model = ReaderLetterModel::query()->find($letter->id()->value) ?? new ReaderLetterModel(['id' => $letter->id()->value]);
        $model->fill([
            'name' => $letter->sender()->name,
            'email' => $letter->sender()->email,
            'phone' => $letter->sender()->phone,
            'title' => $letter->title(),
            'body' => $letter->body(),
            'status' => $letter->status()->value,
            'moderator_note' => $letter->moderatorNote(),
            'moderated_by' => $letter->moderatedBy(),
            'article_id' => $letter->articleId()?->value,
            'ip_hash' => $letter->ipHash(),
            'received_at' => CarbonImmutable::instance(LocalTime::toColumn($letter->receivedAt()) ?? $letter->receivedAt()),
        ])->save();
    }
}
