<?php

declare(strict_types=1);

namespace Elaf\Content\Application\Series;

use Elaf\Content\Domain\Series\SeriesId;
use Elaf\Shared\Application\Validation\Fields;
use Elaf\Shared\Application\Validation\InvalidInput;

final readonly class SeriesInputValidator
{
    public function __construct(private SeriesRepository $series) {}

    public function validate(SeriesInput $input, ?SeriesId $self = null): SeriesData
    {
        $title = Fields::required('title', $input->title, 255);
        $slug = Fields::slug('slug', $input->slug, $title);

        if ($this->series->slugExists($slug, $self)) {
            throw InvalidInput::slugTaken('slug', $slug->value);
        }

        return new SeriesData($title, $slug, Fields::optional('description', $input->description, 2000));
    }
}
