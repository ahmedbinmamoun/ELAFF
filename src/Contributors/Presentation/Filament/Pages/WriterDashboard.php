<?php

declare(strict_types=1);

namespace Elaf\Contributors\Presentation\Filament\Pages;

use App\Models\User;
use Elaf\Content\Infrastructure\Persistence\Models\ArticleModel;
use Elaf\Contributors\Domain\ContributorStatus;
use Filament\Pages\Dashboard;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

/**
 * Pending contributors see only their status here (FR-CON-03); active ones
 * see where their articles stand.
 */
final class WriterDashboard extends Dashboard
{
    public function content(Schema $schema): Schema
    {
        $user = Auth::user();
        $status = $user instanceof User ? ContributorStatus::tryFrom($user->status) : null;

        if ($status !== ContributorStatus::Active) {
            return $schema->components([
                Callout::make(__('contributors.dashboard.pending_title'))
                    ->description(__('contributors.dashboard.pending_body'))
                    ->info(),
            ]);
        }

        $counts = ArticleModel::query()
            ->where('created_by', $user->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return $schema->components([
            Section::make(__('contributors.dashboard.my_articles'))->schema(
                collect(['draft', 'returned', 'in_review', 'approved', 'published'])
                    ->map(static fn (string $s): Text => Text::make(__('admin.status.'.$s).': '.(is_numeric($counts[$s] ?? null) ? (int) $counts[$s] : 0)))
                    ->all(),
            ),
        ]);
    }
}
