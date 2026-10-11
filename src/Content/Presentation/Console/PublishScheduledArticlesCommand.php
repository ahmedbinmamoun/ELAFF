<?php

declare(strict_types=1);

namespace Elaf\Content\Presentation\Console;

use Elaf\Content\Application\Article\PublishDueScheduledArticles\PublishDueScheduledArticlesHandler;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * FR-ART-05, scheduled every minute in routes/console.php.
 */
#[Signature('articles:publish-scheduled')]
#[Description('Publish scheduled articles whose time has come')]
final class PublishScheduledArticlesCommand extends Command
{
    public function handle(PublishDueScheduledArticlesHandler $handler): int
    {
        $result = $handler->handle();

        foreach ($result->published as $id) {
            $this->info(sprintf('Published article %d.', $id->value));
        }

        foreach ($result->failed as $id => $reason) {
            $this->error(sprintf('Article %d not published: %s', $id, $reason));
        }

        return $result->failed === [] ? self::SUCCESS : self::FAILURE;
    }
}
