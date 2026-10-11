<?php

declare(strict_types=1);

namespace Elaf\Shared\Domain\Exception;

/**
 * Base for every business-rule violation, so outer layers can map them
 * (e.g. to HTTP 422) without knowing each concrete type.
 */
abstract class DomainException extends \DomainException {}
