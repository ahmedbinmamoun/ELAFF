<?php

declare(strict_types=1);

use Tests\TestCase;

/*
 * Unit, Application and Architecture tests are plain PHP: no framework boot.
 * Contract tests boot Laravel because they also exercise production adapters.
 */
pest()->extend(TestCase::class)->in('Feature', 'Contract');
