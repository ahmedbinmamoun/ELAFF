<?php

declare(strict_types=1);

use Elaf\Content\Application\Author\AuthorInput;
use Elaf\Content\Application\Author\CreateAuthor\CreateAuthorCommand;
use Elaf\Content\Application\Author\CreateAuthor\CreateAuthorHandler;
use Elaf\Content\Application\Author\UpdateAuthor\UpdateAuthorCommand;
use Elaf\Content\Application\Author\UpdateAuthor\UpdateAuthorHandler;
use Elaf\Content\Application\ContentPermission;
use Elaf\Shared\Application\Authorization\NotAuthorized;
use Elaf\Shared\Application\Validation\InvalidInput;
use Tests\Support\ContentKit;

beforeEach(function (): void {
    $this->kit = (new ContentKit)->grant(ContentPermission::ManageAuthors);
    $this->create = fn (AuthorInput $input) => $this->kit->make(CreateAuthorHandler::class)->handle(new CreateAuthorCommand($input));
});

it('creates a columnist profile linked to a staff user (FR-AUT-01/02)', function (): void {
    $id = ($this->create)(new AuthorInput(
        name: 'آمنة الطيب',
        title: 'كاتبة عمود',
        bio: 'نبذة',
        photoPath: 'authors/1.webp',
        isColumnist: true,
        userId: 4,
        socialLinks: [' X ' => 'https://x.com/amna', 'facebook' => ''],
    ));
    $author = $this->kit->authors->find($id)?->data;

    expect($author?->slug->value)->toBe('آمنة-الطيب')
        ->and($author?->isColumnist)->toBeTrue()
        ->and($author?->userId?->value)->toBe(4)
        ->and($author?->socialLinks)->toBe(['x' => 'https://x.com/amna']);
});

it('links a user to one author only', function (): void {
    $first = ($this->create)(new AuthorInput('الأول', userId: 4));

    expect(fn () => ($this->create)(new AuthorInput('الثاني', userId: 4)))->toThrow(InvalidInput::class, 'already linked');

    $this->kit->make(UpdateAuthorHandler::class)->handle(new UpdateAuthorCommand($first->value, new AuthorInput('الأول', userId: 4, bio: 'جديد')));
    expect($this->kit->authors->find($first)?->data->bio)->toBe('جديد');
});

it('validates social links', function (array $links): void {
    ($this->create)(new AuthorInput('كاتب', socialLinks: $links));
})->with([
    'bad network' => [['face book' => 'https://facebook.com/x']],
    'bad url' => [['facebook' => 'facebook.com/x']],
])->throws(InvalidInput::class);

it('requires the authors permission', function (): void {
    $this->kit->authorizer->revoke(ContentPermission::ManageAuthors->value);

    expect(fn () => ($this->create)(new AuthorInput('كاتب')))->toThrow(NotAuthorized::class);
});
