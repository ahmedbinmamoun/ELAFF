<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Filament\Homepage;

use BackedEnum;
use Elaf\Layout\Application\Homepage\ConfigureBlock\ConfigureHomepageBlockCommand;
use Elaf\Layout\Application\Homepage\ConfigureBlock\ConfigureHomepageBlockHandler;
use Elaf\Layout\Application\Homepage\HomepageBlockInput;
use Elaf\Layout\Application\Homepage\RemoveBlock\RemoveHomepageBlockCommand;
use Elaf\Layout\Application\Homepage\RemoveBlock\RemoveHomepageBlockHandler;
use Elaf\Layout\Domain\Homepage\BlockRegion;
use Elaf\Layout\Infrastructure\Persistence\HomepageBlockModel;
use Elaf\Layout\Presentation\Filament\Homepage\Pages\CreateHomepageBlock;
use Elaf\Layout\Presentation\Filament\Homepage\Pages\EditHomepageBlock;
use Elaf\Layout\Presentation\Filament\Homepage\Pages\ListHomepageBlocks;
use Elaf\Layout\Presentation\Homepage\HomepageBlockRegistry;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseDeleteAction;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * FR-HOME-01: the homepage as an ordered list of blocks; drag to reorder,
 * switch on/off, configure. Each block type supplies its own options form
 * (FR-HOME-03), so this screen never names a type.
 *
 * @extends resource<HomepageBlockModel>
 */
final class HomepageBlockResource extends Resource
{
    protected static ?string $model = HomepageBlockModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 40;

    public static function getModelLabel(): string
    {
        return (string) __('admin.homepage.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.homepage.plural');
    }

    public static function getNavigationGroup(): string
    {
        return (string) __('admin.navigation.site');
    }

    public static function form(Schema $schema): Schema
    {
        $registry = app(HomepageBlockRegistry::class);

        return $schema->components([
            Section::make()->columns(3)->schema([
                Select::make('type')->label(__('admin.homepage.fields.type'))->required()->live()
                    ->options(static fn (): array => array_combine($registry->types(), array_map($registry->label(...), $registry->types())))
                    ->disabledOn('edit'),
                Select::make('region')->label(__('admin.homepage.fields.region'))->required()->default(BlockRegion::Main->value)
                    ->options(self::regions()),
                Toggle::make('is_active')->label(__('admin.homepage.fields.is_active'))->default(true)->inline(false),
            ]),
            Section::make(__('admin.homepage.options_title'))
                ->columns(3)
                ->schema(static fn (Get $get): array => is_string($get('type')) ? $registry->optionsForm($get('type')) : [])
                ->visible(static fn (Get $get): bool => is_string($get('type')) && $registry->optionsForm($get('type')) !== []),
        ]);
    }

    public static function table(Table $table): Table
    {
        $registry = app(HomepageBlockRegistry::class);

        return $table
            ->reorderable('position')
            ->defaultSort('position')
            ->paginated(false)
            ->columns([
                TextColumn::make('type')->label(__('admin.homepage.fields.type'))->formatStateUsing(static fn (string $state): string => $registry->label($state))->weight('bold'),
                TextColumn::make('region')->label(__('admin.homepage.fields.region'))->badge()->formatStateUsing(static fn (string $state): string => self::regions()[$state] ?? $state),
                TextColumn::make('config')->label(__('admin.homepage.options_title'))->color('gray')
                    ->state(static fn (HomepageBlockModel $record): string => self::summary($record)),
                IconColumn::make('is_active')->label(__('admin.homepage.fields.is_active'))->boolean(),
            ])
            ->recordActions([self::toggleAction(), EditAction::make(), self::deleteAction()]);
    }

    /**
     * On/off from the list, through the use case.
     */
    public static function toggleAction(): Action
    {
        return Action::make('toggle')
            ->label(static fn (HomepageBlockModel $record): string => (string) __($record->is_active ? 'admin.homepage.hide' : 'admin.homepage.show'))
            ->icon(static fn (HomepageBlockModel $record): Heroicon => $record->is_active ? Heroicon::OutlinedEyeSlash : Heroicon::OutlinedEye)
            ->color('gray')
            ->action(static function (HomepageBlockModel $record, Action $action): void {
                $ok = UseCaseErrors::forAction(static fn () => app(ConfigureHomepageBlockHandler::class)->handle(new ConfigureHomepageBlockCommand(
                    FormData::actorId(),
                    $record->id,
                    new HomepageBlockInput($record->type, $record->region, is_array($record->config) ? $record->config : [], ! $record->is_active),
                )));

                if (! $ok) {
                    $action->halt();
                }
            });
    }

    public static function deleteAction(): DeleteAction
    {
        return UseCaseDeleteAction::make(static fn (Model $record) => app(RemoveHomepageBlockHandler::class)
            ->handle(new RemoveHomepageBlockCommand(FormData::actorId(), FormData::key($record))));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function input(array $data): HomepageBlockInput
    {
        return new HomepageBlockInput(
            type: FormData::string($data, 'type'),
            region: FormData::string($data, 'region'),
            options: is_array($data['options'] ?? null) ? $data['options'] : [],
            isActive: FormData::bool($data, 'is_active', true),
        );
    }

    /**
     * @return array<string, string>
     */
    public static function regions(): array
    {
        $regions = [];

        foreach (BlockRegion::cases() as $region) {
            $regions[$region->value] = (string) __('admin.homepage.regions.'.$region->value);
        }

        return $regions;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomepageBlocks::route('/'),
            'create' => CreateHomepageBlock::route('/create'),
            'edit' => EditHomepageBlock::route('/{record}/edit'),
        ];
    }

    private static function summary(HomepageBlockModel $record): string
    {
        $parts = [];

        foreach (is_array($record->config) ? $record->config : [] as $key => $value) {
            if (is_scalar($value)) {
                $parts[] = __('admin.homepage.options.'.$key).': '.$value;
            }
        }

        return $parts === [] ? '—' : implode('، ', $parts);
    }
}
