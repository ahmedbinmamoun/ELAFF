<?php

declare(strict_types=1);

namespace Elaf\Contributors\Application\Register;

final readonly class RegisterContributorCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $bio,
        public bool $acceptedTerms,
        public ?string $phone = null,
        public ?string $photoPath = null,
        public ?string $captchaToken = null,
        public ?string $ip = null,
    ) {}
}
