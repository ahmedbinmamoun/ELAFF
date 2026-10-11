<?php

declare(strict_types=1);

namespace Elaf\Shared\Presentation\Filament;

use Closure;
use Filament\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;

/**
 * Filament's delete button, wired to a delete use case instead of
 * $record->delete(), so the use case's checks apply.
 */
final class UseCaseDeleteAction
{
    /**
     * @param  Closure(Model): void  $delete
     */
    public static function make(Closure $delete): DeleteAction
    {
        return DeleteAction::make()->using(static function (Model $record, DeleteAction $action) use ($delete): bool {
            if (! UseCaseErrors::forAction(static fn () => $delete($record))) {
                $action->halt();
            }

            return true;
        });
    }
}
