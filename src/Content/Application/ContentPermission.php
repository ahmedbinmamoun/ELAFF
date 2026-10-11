<?php

declare(strict_types=1);

namespace Elaf\Content\Application;

/**
 * Abilities checked through the Authorizer port. Which role holds which
 * ability is the PRD §2 matrix, seeded by RolesAndPermissionsSeeder.
 */
enum ContentPermission: string
{
    case CreateArticles = 'articles.create';
    case EditAnyArticle = 'articles.edit-any';
    case ReviewArticles = 'articles.review';
    case PublishArticles = 'articles.publish';
    case PromoteArticles = 'articles.promote';
    case ManageTaxonomy = 'taxonomy.manage';
    case ManageAuthors = 'authors.manage';
}
