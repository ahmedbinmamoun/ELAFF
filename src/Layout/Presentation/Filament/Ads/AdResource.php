<?php

declare(strict_types=1);

namespace Elaf\Layout\Presentation\Filament\Ads;

use BackedEnum;
use DateTimeImmutable;
use DateTimeZone;
use Elaf\Layout\Application\Ad\AdInput;
use Elaf\Layout\Application\Ad\DeleteAd\DeleteAdCommand;
use Elaf\Layout\Application\Ad\DeleteAd\DeleteAdHandler;
use Elaf\Layout\Application\LayoutPermission;
use Elaf\Layout\Domain\Ad\Ad;
use Elaf\Layout\Domain\Ad\AdSlot;
use Elaf\Layout\Infrastructure\Persistence\AdModel;
use Elaf\Layout\Presentation\Filament\Ads\Pages\CreateAd;
use Elaf\Layout\Presentation\Filament\Ads\Pages\EditAd;
use Elaf\Layout\Presentation\Filament\Ads\Pages\ListAds;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseDeleteAction;
use Elaf\Shared\Presentation\PublicMedia;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * FR-ADS-01, 02: ads by slot with schedule, weight and on/off, saved
 * through the use cases.
 *
 * @extends resource<AdModel>
 */
final class AdResource extends Resource
{
    protected static ?string $model = AdModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?int $navigationSort = 55;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return (string) __('admin.ad.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return (string) __('admin.ad.plural');
    }

    public static function getNavigationGroup(): string
    {
        return (string) __('admin.navigation.site');
    }

    public static function form(Schema $schema): Schema
    {
        $isImage = static fn (Get $get): bool => $get('kind') !== 'html';

        return $schema->components([
            Section::make()->columns(2)->schema([
                TextInput::make('title')->label(self::label('title'))->required()->maxLength(150)->helperText(__('admin.ad.hints.title')),
                Select::make('slot')->label(self::label('slot'))->required()->options(self::slots())->live()
                    ->helperText(static fn (Get $get): string => self::sizeHint($get('slot'))),
                Radio::make('kind')->label(self::label('kind'))->inline()->live()->default('image')
                    ->options(['image' => __('admin.ad.kinds.image'), 'html' => __('admin.ad.kinds.html')]),
                FileUpload::make('image_path')->label(self::label('image'))->image()->disk(PublicMedia::DISK)->directory('ads')->maxSize(2048)
                    ->visible($isImage)->required($isImage)->columnSpanFull(),
                Textarea::make('html')->label(self::label('html'))->rows(6)->maxLength(20000)
                    ->visible(static fn (Get $get): bool => ! $isImage($get))
                    ->required(static fn (Get $get): bool => ! $isImage($get))
                    ->helperText(static fn (): string => (string) __(Auth::user()?->can(LayoutPermission::RawAdHtml->value) ? 'admin.ad.hints.html_raw' : 'admin.ad.hints.html_sanitized'))
                    ->extraInputAttributes(['dir' => 'ltr'])
                    ->columnSpanFull(),
                TextInput::make('target_url')->label(self::label('target_url'))->url()->maxLength(2048)
                    ->required($isImage)->extraInputAttributes(['dir' => 'ltr']),
                TextInput::make('weight')->label(self::label('weight'))->integer()->minValue(Ad::MIN_WEIGHT)->maxValue(Ad::MAX_WEIGHT)->default(1)
                    ->helperText(__('admin.ad.hints.weight')),
                DateTimePicker::make('starts_at')->label(self::label('starts_at'))->seconds(false),
                DateTimePicker::make('ends_at')->label(self::label('ends_at'))->seconds(false),
                Toggle::make('is_active')->label(self::label('is_active'))->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('image_path')->label(self::label('image'))->disk(PublicMedia::DISK)->imageHeight(40),
                TextColumn::make('title')->label(self::label('title'))->searchable(),
                TextColumn::make('slot')->label(self::label('slot'))->badge()->formatStateUsing(static fn (string $state): string => self::slots()[$state] ?? $state),
                TextColumn::make('starts_at')->label(self::label('starts_at'))->dateTime('Y-m-d H:i')->placeholder('—')->toggleable(),
                TextColumn::make('ends_at')->label(self::label('ends_at'))->dateTime('Y-m-d H:i')->placeholder('—'),
                TextColumn::make('weight')->label(self::label('weight'))->numeric(),
                TextColumn::make('clicks_count')->label(self::label('clicks'))->numeric()->sortable(),
                IconColumn::make('is_active')->label(self::label('is_active'))->boolean(),
            ])
            ->filters([SelectFilter::make('slot')->label(self::label('slot'))->options(self::slots())])
            ->recordActions([EditAction::make(), self::deleteAction()]);
    }

    public static function deleteAction(): DeleteAction
    {
        return UseCaseDeleteAction::make(static fn (Model $record) => app(DeleteAdHandler::class)
            ->handle(new DeleteAdCommand(FormData::actorId(), FormData::key($record))));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function input(array $data): AdInput
    {
        $html = FormData::string($data, 'kind') === 'html';

        return new AdInput(
            title: FormData::string($data, 'title'),
            slot: FormData::string($data, 'slot'),
            imagePath: $html ? null : FormData::text($data, 'image_path'),
            html: $html ? FormData::text($data, 'html') : null,
            targetUrl: FormData::text($data, 'target_url'),
            startsAt: self::moment($data['starts_at'] ?? null),
            endsAt: self::moment($data['ends_at'] ?? null),
            weight: FormData::int($data, 'weight') ?? 1,
            isActive: FormData::bool($data, 'is_active', true),
        );
    }

    /**
     * @return array<string, string>
     */
    public static function slots(): array
    {
        $slots = [];

        foreach (AdSlot::cases() as $slot) {
            $slots[$slot->value] = (string) __('admin.ad.slots.'.$slot->value);
        }

        return $slots;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAds::route('/'),
            'create' => CreateAd::route('/create'),
            'edit' => EditAd::route('/{record}/edit'),
        ];
    }

    private static function sizeHint(mixed $slot): string
    {
        $slot = AdSlot::tryFrom(is_string($slot) ? $slot : '');

        return $slot === null ? '' : (string) __('admin.ad.hints.size', ['width' => $slot->width(), 'height' => $slot->height()]);
    }

    /**
     * Form times are wall-clock in the site's timezone.
     */
    private static function moment(mixed $value): ?DateTimeImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $moment = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', strlen($value) === 16 ? $value.':00' : $value, new DateTimeZone(date_default_timezone_get()));

        return $moment === false ? null : $moment;
    }

    private static function label(string $field): string
    {
        return (string) __('admin.ad.fields.'.$field);
    }
}
