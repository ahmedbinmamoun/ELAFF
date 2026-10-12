<?php

declare(strict_types=1);

namespace Elaf\Settings\Presentation\Filament;

use BackedEnum;
use Elaf\Settings\Application\SettingsPermission;
use Elaf\Settings\Application\Site\SettingsReader;
use Elaf\Settings\Application\Site\SiteSettings;
use Elaf\Settings\Application\Site\UpdateSettings\UpdateSettingsCommand;
use Elaf\Settings\Application\Site\UpdateSettings\UpdateSettingsHandler;
use Elaf\Shared\Presentation\Filament\FormData;
use Elaf\Shared\Presentation\Filament\UseCaseErrors;
use Elaf\Shared\Presentation\PublicMedia;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

/**
 * FR-SET-01: site settings, Admin only (PRD §2). Saved through
 * UpdateSettings, which refreshes the cached copy (FR-SET-02).
 *
 * @property-read Schema $form
 */
final class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 90;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return (string) __('admin.settings.title');
    }

    public static function getNavigationGroup(): string
    {
        return (string) __('admin.navigation.administration');
    }

    public function getTitle(): string
    {
        return (string) __('admin.settings.title');
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can(SettingsPermission::ManageSettings->value) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(app(SettingsReader::class)->site()->toArray());
    }

    public function form(Schema $schema): Schema
    {
        $image = static fn (string $key, string $dir): FileUpload => FileUpload::make($key)
            ->label(self::label($key))
            ->image()
            ->disk(PublicMedia::DISK)
            ->directory('settings/'.$dir)
            ->maxSize(2048);

        return $schema->statePath('data')->components([
            Tabs::make()->tabs([
                Tab::make(__('admin.settings.tabs.identity'))->columns(2)->schema([
                    TextInput::make('name')->label(self::label('name'))->required()->maxLength(60),
                    TextInput::make('logo_suffix')->label(self::label('logo_suffix'))->maxLength(40),
                    TextInput::make('motto')->label(self::label('motto'))->maxLength(120),
                    TextInput::make('description')->label(self::label('description'))->maxLength(300),
                    $image('logo_path', 'logo')->helperText(__('admin.settings.hints.logo')),
                    $image('favicon_path', 'favicon')->helperText(__('admin.settings.hints.favicon')),
                    $image('default_og_image_path', 'og')->helperText(__('admin.settings.hints.og_image')),
                ]),
                Tab::make(__('admin.settings.tabs.contact'))->columns(2)->schema([
                    TextInput::make('contact_email')->label(self::label('contact_email'))->email()->maxLength(190),
                    TextInput::make('phone')->label(self::label('phone'))->tel()->maxLength(30),
                    TextInput::make('address')->label(self::label('address'))->maxLength(300)->columnSpanFull(),
                    Textarea::make('footer_text')->label(self::label('footer_text'))->rows(2)->maxLength(500)->columnSpanFull(),
                ]),
                Tab::make(__('admin.settings.tabs.social'))->columns(2)->schema([
                    TextInput::make('whatsapp_url')->label(self::label('whatsapp_url'))->url()->maxLength(2048),
                    TextInput::make('facebook_url')->label(self::label('facebook_url'))->url()->maxLength(2048),
                    TextInput::make('youtube_url')->label(self::label('youtube_url'))->url()->maxLength(2048),
                    TextInput::make('x_url')->label(self::label('x_url'))->url()->maxLength(2048),
                ]),
                Tab::make(__('admin.settings.tabs.masthead'))->columns(3)->schema([
                    TextInput::make('masthead_editor_in_chief')->label(self::label('masthead_editor_in_chief'))->maxLength(120),
                    TextInput::make('masthead_editorial_advisor')->label(self::label('masthead_editorial_advisor'))->maxLength(120),
                    TextInput::make('masthead_publisher')->label(self::label('masthead_publisher'))->maxLength(120),
                ]),
                Tab::make(__('admin.settings.tabs.editorial'))->schema([
                    Section::make(__('admin.settings.contributors'))->schema([
                        Toggle::make('contributors_require_account_approval')->label(self::label('contributors_require_account_approval')),
                        Toggle::make('contributors_editor_can_publish')->label(self::label('contributors_editor_can_publish')),
                        Toggle::make('contributors_guest_byline_label')->label(self::label('contributors_guest_byline_label')),
                    ]),
                    TextInput::make('analytics_id')->label(self::label('analytics_id'))->placeholder('G-XXXXXXXXXX')->maxLength(30)
                        ->helperText(__('admin.settings.hints.analytics')),
                ]),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([Actions::make([Action::make('save')->label(__('admin.settings.save'))->submit('save')])]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        UseCaseErrors::forForm(static fn () => app(UpdateSettingsHandler::class)->handle(new UpdateSettingsCommand(
            FormData::actorId(),
            self::settings($data),
        )));

        Notification::make()->success()->title(__('admin.settings.saved'))->send();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function settings(array $data): SiteSettings
    {
        return new SiteSettings(
            name: FormData::string($data, 'name'),
            logoSuffix: FormData::text($data, 'logo_suffix'),
            motto: FormData::text($data, 'motto'),
            description: FormData::text($data, 'description'),
            logoPath: FormData::text($data, 'logo_path'),
            faviconPath: FormData::text($data, 'favicon_path'),
            defaultOgImagePath: FormData::text($data, 'default_og_image_path'),
            contactEmail: FormData::text($data, 'contact_email'),
            phone: FormData::text($data, 'phone'),
            address: FormData::text($data, 'address'),
            footerText: FormData::text($data, 'footer_text'),
            facebookUrl: FormData::text($data, 'facebook_url'),
            whatsappUrl: FormData::text($data, 'whatsapp_url'),
            youtubeUrl: FormData::text($data, 'youtube_url'),
            xUrl: FormData::text($data, 'x_url'),
            mastheadEditorInChief: FormData::text($data, 'masthead_editor_in_chief'),
            mastheadEditorialAdvisor: FormData::text($data, 'masthead_editorial_advisor'),
            mastheadPublisher: FormData::text($data, 'masthead_publisher'),
            analyticsId: FormData::text($data, 'analytics_id'),
            requireContributorApproval: FormData::bool($data, 'contributors_require_account_approval'),
            editorsCanPublishContributorArticles: FormData::bool($data, 'contributors_editor_can_publish'),
            guestBylineLabel: FormData::bool($data, 'contributors_guest_byline_label'),
        );
    }

    private static function label(string $key): string
    {
        return (string) __('admin.settings.fields.'.$key);
    }
}
