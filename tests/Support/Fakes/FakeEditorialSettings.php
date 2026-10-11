<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Elaf\Content\Application\Article\EditorialSettings;
use Elaf\Contributors\Application\ContributorSettings;

final class FakeEditorialSettings implements ContributorSettings, EditorialSettings
{
    public bool $editorsCanPublish = false;

    public bool $requireApproval = true;

    public bool $guestLabel = true;

    public function editorsCanPublishContributorArticles(): bool
    {
        return $this->editorsCanPublish;
    }

    public function requireAccountApproval(): bool
    {
        return $this->requireApproval;
    }

    public function showGuestBylineLabel(): bool
    {
        return $this->guestLabel;
    }
}
