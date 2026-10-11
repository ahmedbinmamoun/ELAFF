<?php

declare(strict_types=1);

namespace Elaf\Content\Domain\Article;

enum ArticleOrigin: string
{
    case Staff = 'staff';
    case Contributor = 'contributor';
}
