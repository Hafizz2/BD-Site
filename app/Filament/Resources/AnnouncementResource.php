<?php

namespace App\Filament\Resources;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementChannel;
use App\Enums\AnnouncementStatus;
use App\Enums\MemberTeam;
use App\Filament\Resources\AnnouncementResource\Pages;
use App\Filament\Support\Bilingual;
use App\Jobs\SendAnnouncement;
use App\Models\Announcement;
use App\Models\Batch;
use App\Models\Member;
use App\Models\Program;
use App\Models\Student;
use App\Services\Announcements\AudienceResolver;
use App\Services\Telegram\TelegramGateway;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Throwable;
use UnitEnum;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|UnitEnum|null $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        $audienceIs = fn (AnnouncementAudience $a) => fn (Get $get) => in_array($get('audience'), [$a, $a->value], true);

        return $schema->columns(3)->components([
            Section::make('Message')->columnSpan(['lg' => 2])->schema([
                Bilingual::text('title', 'Title', true, 150),
                Bilingual::rich('body', 'Message', 'announcements'),
                SpatieMediaLibraryFileUpload::make('image')->collection('image')->image()->maxSize(5120)->label('Image (optional)'),
            ]),
            Section::make('Audience & delivery')->columnSpan(['lg' => 1])->schema([
                Select::make('audience')->options(AnnouncementAudience::class)->default(AnnouncementAudience::Everyone)->required()->live()->native(false),
                Select::make('audience_filter.program_ids')->label('Programs')->multiple()->options(fn () => Program::orderBy('sort')->pluck('name', 'id'))
                    ->visible($audienceIs(AnnouncementAudience::Program))->required($audienceIs(AnnouncementAudience::Program))->live(),
                Select::make('audience_filter.batch_ids')->label('Batches')->multiple()
                    ->options(fn () => Batch::with('program')->latest('start_date')->get()->mapWithKeys(fn ($b) => [$b->id => $b->label]))
                    ->visible($audienceIs(AnnouncementAudience::Batch))->required($audienceIs(AnnouncementAudience::Batch))->live(),
                CheckboxList::make('audience_filter.teams')->label('Teams')->options(MemberTeam::class)
                    ->visible($audienceIs(AnnouncementAudience::Team))->required($audienceIs(AnnouncementAudience::Team))->live(),
                Select::make('audience_filter.student_ids')->label('Students')->multiple()->searchable()
                    ->getSearchResultsUsing(fn (string $s) => Student::where('full_name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")->limit(30)->pluck('full_name', 'id'))
                    ->getOptionLabelsUsing(fn (array $values) => Student::whereKey($values)->pluck('full_name', 'id'))
                    ->visible($audienceIs(AnnouncementAudience::Custom))->live(),
                Select::make('audience_filter.member_ids')->label('Members')->multiple()->searchable()
                    ->options(fn () => Member::orderBy('full_name')->pluck('full_name', 'id'))
                    ->visible($audienceIs(AnnouncementAudience::Custom))->live(),
                Text::make(fn (Get $get) => self::previewText($get))->color('gray'),
                CheckboxList::make('channels')->options(AnnouncementChannel::class)->default(['telegram_bot', 'website'])->required()->live(),
                DateTimePicker::make('scheduled_at')->label('Schedule for (optional)')->native(false)->seconds(false)->minDate(now()->subMinute())
                    ->helperText('Leave empty and use "Send now", or pick a time and use "Schedule".'),
            ]),
        ]);
    }

    private static function previewText(Get $get): HtmlString
    {
        $aud = $get('audience');
        $aud = $aud instanceof AnnouncementAudience ? $aud : AnnouncementAudience::tryFrom((string) $aud);
        if (! $aud) {
            return new HtmlString('');
        }
        $p = app(AudienceResolver::class)->preview($aud, (array) ($get('audience_filter') ?? []));

        return new HtmlString("👥 <b>{$p['total']}</b> people in this audience · <b>{$p['reachable']}</b> linked to the Telegram bot");
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Delivery')->columns(5)->schema([
                TextEntry::make('status')->badge(),
                TextEntry::make('stats.sent')->label('Delivered')->state(fn (Announcement $r) => $r->deliveryStats()['sent'])->color('success')->weight('bold'),
                TextEntry::make('stats.failed')->label('Failed')->state(fn (Announcement $r) => $r->deliveryStats()['failed'])->color('danger')->weight('bold'),
                TextEntry::make('stats.blocked')->label('Blocked the bot')->state(fn (Announcement $r) => $r->deliveryStats()['blocked'])->color('warning')->weight('bold'),
                TextEntry::make('stats.queued')->label('Queued')->state(fn (Announcement $r) => $r->deliveryStats()['queued']),
                TextEntry::make('audience')->badge()->color('gray'),
                TextEntry::make('channels')->badge()->formatStateUsing(fn ($state) => AnnouncementChannel::tryFrom($state)?->getLabel() ?? $state),
                TextEntry::make('scheduled_at')->dateTime()->placeholder('—'),
                TextEntry::make('sent_at')->dateTime()->placeholder('—'),
                TextEntry::make('creator.name')->label('Created by'),
            ]),
            Grid::make(['default' => 1, 'lg' => 2])->columnSpanFull()->schema([
                Section::make('English')->schema([
                    TextEntry::make('title')->weight('bold'),
                    TextEntry::make('body')->html()->hiddenLabel(),
                ]),
                Section::make('አማርኛ')->schema([
                    TextEntry::make('title_am')->label('Title')->weight('bold')->placeholder('—'),
                    TextEntry::make('body_am')->html()->hiddenLabel()->placeholder('—'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->poll('10s')
            ->columns([
                TextColumn::make('title')->searchable()->weight('semibold')->wrap()->description(fn (Announcement $r) => $r->audience?->getLabel()),
                TextColumn::make('status')->badge(),
                TextColumn::make('delivered')->label('Delivered')->state(fn (Announcement $r) => ($s = $r->deliveryStats())['sent'].' / '.array_sum($s))
                    ->visible(fn () => true),
                TextColumn::make('scheduled_at')->dateTime('d M H:i')->placeholder('—')->label('Scheduled'),
                TextColumn::make('sent_at')->since()->placeholder('—')->label('Sent'),
            ])
            ->filters([SelectFilter::make('status')->options(AnnouncementStatus::class)])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()->visible(fn (Announcement $r) => in_array($r->status, [AnnouncementStatus::Draft, AnnouncementStatus::Scheduled], true)),
                    self::testAction(),
                    self::sendNowAction(),
                    self::scheduleAction(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    public static function testAction(): Action
    {
        return Action::make('test')->label('Send test to me')->icon('heroicon-o-beaker')->color('gray')
            ->action(function (Announcement $record) {
                $chat = auth()->user()?->member?->telegram_chat_id;
                if (! $chat) {
                    Notification::make()->warning()->title('Your account is not linked to Telegram')
                        ->body('Link your member record (Members → your name → Telegram link) to receive tests.')->send();

                    return;
                }
                try {
                    app(TelegramGateway::class)->sendMessage($chat, '🧪 TEST'."\n\n".SendAnnouncement::renderFor($record, auth()->user()->member->locale ?: 'am'));
                    Notification::make()->success()->title('Test sent to your Telegram')->send();
                } catch (Throwable $e) {
                    Notification::make()->danger()->title('Telegram error')->body($e->getMessage())->send();
                }
            });
    }

    public static function sendNowAction(): Action
    {
        return Action::make('sendNow')->label('Send now')->icon('heroicon-o-paper-airplane')->color('success')
            ->visible(fn (Announcement $r) => in_array($r->status, [AnnouncementStatus::Draft, AnnouncementStatus::Scheduled, AnnouncementStatus::Failed], true))
            ->requiresConfirmation()
            ->modalDescription(function (Announcement $r) {
                $p = app(AudienceResolver::class)->preview($r->audience, $r->audience_filter ?? []);

                return "This sends to {$p['reachable']} linked Telegram users (of {$p['total']} in the audience) and posts to the selected channels.";
            })
            ->action(function (Announcement $r) {
                if ($r->status === AnnouncementStatus::Failed) {
                    $r->forceFill(['status' => AnnouncementStatus::Draft])->save();
                }
                $r->forceFill(['scheduled_at' => null])->save();
                SendAnnouncement::dispatch($r->id);
                Notification::make()->success()->title('Sending started')->body('Delivery stats update on the announcement page.')->send();
            });
    }

    public static function scheduleAction(): Action
    {
        return Action::make('schedule')->icon('heroicon-o-clock')->color('info')
            ->visible(fn (Announcement $r) => $r->status === AnnouncementStatus::Draft)
            ->schema([DateTimePicker::make('scheduled_at')->required()->native(false)->seconds(false)->minDate(now())->default(fn (Announcement $r) => $r->scheduled_at)])
            ->action(function (Announcement $r, array $data) {
                $r->forceFill(['scheduled_at' => $data['scheduled_at'], 'status' => AnnouncementStatus::Scheduled])->save();
                Notification::make()->success()->title('Scheduled')->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'view' => Pages\ViewAnnouncement::route('/{record}'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
