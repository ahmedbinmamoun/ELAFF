<?php

declare(strict_types=1);

namespace Elaf\Publishing\Infrastructure\Persistence;

use Carbon\CarbonImmutable;
use Elaf\Content\Infrastructure\Persistence\Eloquent\LocalTime;
use Elaf\Publishing\Domain\Issue\Exception\IssueNotFound;
use Elaf\Publishing\Domain\Issue\Issue;
use Elaf\Publishing\Domain\Issue\IssueFile;
use Elaf\Publishing\Domain\Issue\IssueId;
use Elaf\Publishing\Domain\Issue\IssueNumber;
use Elaf\Publishing\Domain\Issue\IssuePage;
use Elaf\Publishing\Domain\Issue\IssueRendering;
use Elaf\Publishing\Domain\Issue\IssueRepository;
use Elaf\Publishing\Domain\Issue\ProcessingStatus;
use Elaf\Publishing\Domain\Issue\PublicationDate;
use Illuminate\Database\ConnectionInterface;

final readonly class EloquentIssueRepository implements IssueRepository
{
    public function __construct(private ConnectionInterface $db) {}

    public function nextId(): IssueId
    {
        return IssueId::fromInt((int) $this->db->table('issue_id_sequence')->insertGetId([]));
    }

    public function get(IssueId $id): Issue
    {
        $model = IssueModel::query()->with('pages')->find($id->value);

        if ($model === null) {
            throw IssueNotFound::withId($id);
        }

        return $this->toDomain($model);
    }

    public function findByNumber(IssueNumber $number): ?Issue
    {
        $model = IssueModel::query()->with('pages')->where('number', $number->value)->first();

        return $model === null ? null : $this->toDomain($model);
    }

    public function save(Issue $issue): void
    {
        $this->db->transaction(function () use ($issue): void {
            $model = IssueModel::query()->find($issue->id()->value) ?? new IssueModel;
            $rendering = $issue->rendering();

            if (! $model->exists) {
                $model->id = $issue->id()->value;
            }

            $model->fill([
                'number' => $issue->number()->value,
                'published_on' => $issue->publishedOn()->value,
                'title' => $issue->title(),
                'pdf_path' => $issue->file()->path,
                'pdf_size' => $issue->file()->size,
                'pdf_checksum' => $issue->file()->checksum,
                'cover_path' => $rendering?->cover()->imagePath,
                'cover_thumb_path' => $rendering?->cover()->thumbPath,
                'social_image_path' => $rendering?->socialImagePath,
                'page_count' => $rendering?->pageCount(),
                'processing_status' => $issue->status()->value,
                'processing_error' => $issue->processingError(),
                'processed_at' => $issue->processedAt() === null ? null : CarbonImmutable::instance(LocalTime::toColumn($issue->processedAt()) ?? $issue->processedAt()),
            ])->save();

            $model->pages()->delete();

            foreach ($rendering->pages ?? [] as $page) {
                $model->pages()->create([
                    'page_number' => $page->number,
                    'image_path' => $page->imagePath,
                    'thumb_path' => $page->thumbPath,
                    'width' => $page->width,
                    'height' => $page->height,
                ]);
            }
        });
    }

    public function delete(IssueId $id): void
    {
        IssueModel::query()->whereKey($id->value)->delete();
    }

    private function toDomain(IssueModel $model): Issue
    {
        $status = ProcessingStatus::from($model->processing_status);
        $rendering = null;

        if ($status === ProcessingStatus::Ready) {
            $rendering = new IssueRendering(
                array_values($model->pages->map(static fn (IssuePageModel $page): IssuePage => new IssuePage(
                    $page->page_number, $page->image_path, $page->thumb_path, $page->width, $page->height,
                ))->all()),
                (string) $model->social_image_path,
            );
        }

        return Issue::reconstitute(
            IssueId::fromInt($model->id),
            new IssueNumber($model->number),
            PublicationDate::fromString($model->published_on->format('Y-m-d')),
            $model->title,
            new IssueFile($model->pdf_path, $model->pdf_size, $model->pdf_checksum),
            $status,
            $rendering,
            $model->processing_error,
            LocalTime::fromColumn($model->processed_at),
        );
    }
}
