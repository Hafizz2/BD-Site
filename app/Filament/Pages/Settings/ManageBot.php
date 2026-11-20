<?php

namespace App\Filament\Pages\Settings;

use App\Filament\Support\Bilingual;
use App\Services\Telegram\TelegramGateway;
use App\Settings\BotSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Throwable;
use UnitEnum;

class ManageBot extends SettingsPage
{
    protected static string $settings = BotSettings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-paper-airplane';

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationLabel = 'Telegram bot';

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testGroup')->label('Send test to finance group')->icon('heroicon-o-beaker')->color('gray')
                ->action(function () {
                    $chat = app(BotSettings::class)->finance_group_chat_id;
                    if (! $chat) {
                        Notification::make()->warning()->title('Set the finance group chat ID first')->send();

                        return;
                    }
                    try {
                        app(TelegramGateway::class)->sendMessage($chat, '✅ Ye Birhan Dana bot is connected to this group.');
                        Notification::make()->success()->title('Test message sent')->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title('Telegram error')->body($e->getMessage())->send();
                    }
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Bot')->description('The bot token is stored only in the server .env file (TELEGRAM_TOKEN).')->columns(2)->schema([
                Toggle::make('enabled')->label('Bot notifications enabled')->columnSpanFull(),
                TextInput::make('bot_username')->prefix('@')->helperText('Used for personal deep links: t.me/<username>?start=…')
                    ->dehydrateStateUsing(fn ($state) => ltrim((string) $state, '@')),
                TextInput::make('finance_group_chat_id')->label('Finance group chat ID')->helperText('e.g. -1001234567890. Add the bot to the group first.'),
                TextInput::make('public_channel_id')->label('Public channel')->helperText('@channelusername or -100… (bot must be an admin).'),
            ]),
            Section::make('Welcome message')->schema([Bilingual::textarea('welcome_message', 'Welcome message', false, 1000, 3)]),
            Section::make('Weekly finance summary')->columns(3)->schema([
                Toggle::make('weekly_summary_enabled')->inline(false),
                Select::make('weekly_summary_day')->options(collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])->mapWithKeys(fn ($d) => [$d => ucfirst($d)]))->native(false),
                TextInput::make('weekly_summary_time')->type('time'),
            ]),
        ]);
    }
}
