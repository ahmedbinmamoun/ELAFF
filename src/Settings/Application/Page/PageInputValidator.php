<?php

declare(strict_types=1);

namespace Elaf\Settings\Application\Page;

use Elaf\Settings\Domain\Page\PageId;
use Elaf\Shared\Application\Html\HtmlSanitizer;
use Elaf\Shared\Application\Html\SanitizerProfile;
use Elaf\Shared\Application\Validation\Fields;
use Elaf\Shared\Application\Validation\InvalidInput;

final readonly class PageInputValidator
{
    public const MAX_BODY = 100_000;

    public function __construct(
        private PageRepository $pages,
        private HtmlSanitizer $sanitizer,
    ) {}

    public function validate(PageInput $input, ?PageId $self = null): PageData
    {
        $title = Fields::required('title', $input->title, 150);
        $slug = Fields::slug('slug', $input->slug, $title);

        if ($this->pages->slugExists($slug, $self)) {
            throw InvalidInput::slugTaken('slug', $slug->value);
        }

        if (mb_strlen($input->body) > self::MAX_BODY) {
            throw InvalidInput::tooLong('body', self::MAX_BODY);
        }

        return new PageData(
            title: $title,
            slug: $slug,
            body: $this->sanitizer->sanitize($input->body, SanitizerProfile::Staff),
            isPublished: $input->isPublished,
            seoTitle: Fields::optional('seo_title', $input->seoTitle, 255),
            seoDescription: Fields::optional('seo_description', $input->seoDescription, 500),
        );
    }
}
