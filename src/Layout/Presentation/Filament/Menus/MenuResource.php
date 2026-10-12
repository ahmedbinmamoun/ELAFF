<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Filament\Menus;

use BackedEnum;
use Closure;
use Elaf\Content\Infrastructure\Persistence\Models\CategoryModel;
use Elaf\Layout\Application\Menu\MenuItemData;
use Elaf\Layout\Application\Menu\MenuItemInput;
use Elaf\Layout\Domain\Menu\MenuItemType;
use Elaf\Layout\Infrastructure\Persistence\MenuModel;
use Elaf\Layout\Presentation\Filament\Menus\Pages\EditMenu;
use Elaf\Layout\Presentation\Filament\Menus\Pages\ListMenus;
use Elaf\Settings\Infrastructure\Persistence\PageModel;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Icons;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * FR-NAV-01: header, sections and footer menus. Items are edited as a
 * nested, reorderable list and saved through SaveMenu.
 *
 * @extends resource<MenuModel>
 */
final class MenuResource extends Resource
{
    protected static ?string $model = MenuModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?int $navigationSort = 50;

    public static function getModelLabel(): string
    {
        return (string) __('admin.menu.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.menu.plural');
    }

    public static function getNavigationGroup(): string
    {
        return (string) __('admin.navigation.site');
    }

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record instanceof MenuModel ? self::menuLabel($record->key) : '';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Repeater::make('items')
                ->hiddenLabel()
                ->schema([...self::itemFields(), Repeater::make('children')
                    ->label(__('admin.menu.children'))
                    ->schema(self::itemFields())
                    ->columns(3)
                    ->reorderableWithButtons()
                    ->collapsible()
                    ->itemLabel(static fn (array $state): ?string => is_string($state['label'] ?? null) ? $state['label'] : null)
                    ->addActionLabel(__('admin.menu.add_child'))
                    ->defaultItems(0)
                    ->columnSpanFull()])
                ->columns(3)
                ->reorderableWithButtons()
                ->collapsible()
                ->collapsed()
                ->itemLabel(static fn (array $state): ?string => is_string($state['label'] ?? null) ? $state['label'] : null)
                ->addActionLabel(__('admin.menu.add'))
                ->defaultItems(0)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(static fn (Builder $query): Builder => $query->withCount('items'))
            ->columns([
                TextColumn::make('key')->label(__('admin.menu.name'))->formatStateUsing(static fn (string $state): string => self::menuLabel($state)),
                TextColumn::make('items_count')->label(__('admin.menu.items_count'))->numeric(),
            ])
            ->recordActions([EditAction::make()])
            ->paginated(false);
    }

    public static function menuLabel(string $key): string
    {
        return (string) __('admin.menu.keys.'.$key);
    }

    /**
     * @param  list<MenuItemData>  $items
     * @return list<array<string, mixed>>
     */
    public static function toForm(array $items): array
    {
        return array_map(static fn (MenuItemData $i): array => [
            'label' => $i->label,
            'type' => $i->type->value,
            'category_id' => $i->type === MenuItemType::Category ? $i->targetId : null,
            'page_id' => $i->type === MenuItemType::Page ? $i->targetId : null,
            'url' => $i->url,
            'icon' => $i->icon,
            'is_visible' => $i->isVisible,
            'children' => self::toForm($i->children),
        ], $items);
    }

    /**
     * @param  array<array-key, mixed>  $rows  repeater state
     * @return list<MenuItemInput>
     */
    public static function toInput(array $rows): array
    {
        $items = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $type = FormData::string($row, 'type');
            $items[] = new MenuItemInput(
                label: FormData::string($row, 'label'),
                type: $type,
                targetId: FormData::int($row, $type === MenuItemType::Page->value ? 'page_id' : 'category_id'),
                url: FormData::text($row, 'url'),
                icon: FormData::text($row, 'icon'),
                isVisible: FormData::bool($row, 'is_visible', true),
                children: self::toInput(is_array($row['children'] ?? null) ? $row['children'] : []),
            );
        }

        return $items;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
            'edit' => EditMenu::route('/{record}/edit'),
        ];
    }

    /**
     * @return list<\Filament\Schemas\Components\Component|\Filament\Forms\Components\Field>
     */
    private static function itemFields(): array
    {
        $is = static fn (MenuItemType $type): Closure => static fn (Get $get): bool => $get('type') === $type->value;

        return [
            TextInput::make('label')->label(__('admin.menu.fields.label'))->required()->maxLength(60),
            Select::make('type')->label(__('admin.menu.fields.type'))->required()->live()->default(MenuItemType::Category->value)
                ->options(array_combine(
                    array_map(static fn (MenuItemType $t): string => $t->value, MenuItemType::cases()),
                    array_map(static fn (MenuItemType $t): string => (string) __('admin.menu.types.'.$t->value), MenuItemType::cases()),
                )),
            Select::make('category_id')->label(__('admin.menu.fields.category'))->required()->visible($is(MenuItemType::Category))
                ->options(static fn (): array => CategoryModel::query()->orderBy('sort_order')->pluck('name', 'id')->all()),
            Select::make('page_id')->label(__('admin.menu.fields.page'))->required()->visible($is(MenuItemType::Page))
                ->options(static fn (): array => PageModel::query()->orderBy('title')->pluck('title', 'id')->all()),
            TextInput::make('url')->label(__('admin.menu.fields.url'))->required()->maxLength(2048)->visible($is(MenuItemType::Url))
                ->placeholder('/letters أو https://…'),
            Select::make('icon')->label(__('admin.menu.fields.icon'))->options(Icons::SECTION_ICONS)
                ->helperText(__('admin.menu.hints.icon')),
            Toggle::make('is_visible')->label(__('admin.menu.fields.is_visible'))->default(true)->inline(false),
        ];
    }
}
