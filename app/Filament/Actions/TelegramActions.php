<?php

namespace App\Filament\Actions;

use App\Enums\NotificationType;
use App\Models\Member;
use App\Models\Student;
use App\Services\Telegram\Notifier;
use App\Services\Telegram\TelegramLinker;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

/**
 * Reusable Telegram actions for Students and Members.
 */
final class TelegramActions
{
    public static function copyLink(): Action
    {
        return Action::make('telegramLink')
            ->label('Telegram link')
            ->icon('heroicon-o-link')
            ->color('info')
            ->modalHeading('Personal Telegram link')
            ->modalDescription('Send this link to the person. Opening it links their Telegram to this record (valid 7 days, single use).')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(function (Student|Member $record) {
                $link = app(TelegramLinker::class)->deepLinkFor($record);

                return new HtmlString($link
                    ? '<div style="display:flex;gap:.5rem;align-items:center"><input readonly value="'.e($link).'" onclick="this.select()" style="flex:1;padding:.6rem .8rem;border:1px solid #d6d3d1;border-radius:.6rem;font-family:monospace;font-size:.85rem"><button type="button" onclick="navigator.clipboard.writeText(\''.e($link).'\');this.innerText=\'Copied ✓\'" style="padding:.6rem 1rem;border-radius:.6rem;background:#0b3d2a;color:#fff;font-weight:600">Copy</button></div>'
                    : '<p>Set the bot username in Settings → Telegram bot first.</p>');
            });
    }

    public static function qr(): Action
    {
        return Action::make('telegramQr')
            ->label('QR code')
            ->icon('heroicon-o-qr-code')
            ->color('gray')
            ->modalHeading('Scan to link Telegram')
            ->modalWidth('sm')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Close')
            ->modalContent(function (Student|Member $record) {
                $link = app(TelegramLinker::class)->deepLinkFor($record);
                if (! $link) {
                    return new HtmlString('<p>Set the bot username in Settings → Telegram bot first.</p>');
                }
                $svg = (new Writer(new ImageRenderer(new RendererStyle(260, 1), new SvgImageBackEnd)))->writeString($link);

                return new HtmlString('<div style="display:grid;place-items:center;gap:.75rem"><div style="padding:12px;background:#fff;border-radius:16px;border:4px solid #c9a45c">'.$svg.'</div><div style="font-weight:600">'.e($record->full_name).'</div></div>');
            });
    }

    public static function message(): Action
    {
        return Action::make('telegramMessage')
            ->label('Send Telegram message')
            ->icon('heroicon-o-paper-airplane')
            ->color('info')
            ->visible(fn (Student|Member $record) => $record->isTelegramReachable())
            ->schema([Textarea::make('message')->required()->rows(5)->maxLength(3500)])
            ->action(function (Student|Member $record, array $data) {
                app(Notifier::class)->notify($record, NotificationType::DirectMessage, e($data['message']));
                Notification::make()->success()->title('Message queued')->send();
            });
    }

    public static function bulkMessage(): BulkAction
    {
        return BulkAction::make('telegramBulk')
            ->label('Send Telegram message')
            ->icon('heroicon-o-paper-airplane')
            ->schema([Textarea::make('message')->required()->rows(5)->maxLength(3500)])
            ->action(function (Collection $records, array $data) {
                $n = 0;
                foreach ($records as $r) {
                    $n += (int) (bool) app(Notifier::class)->notify($r, NotificationType::DirectMessage, e($data['message']));
                }
                Notification::make()->success()->title("Queued for {$n} linked people")->body($records->count() - $n.' not linked to the bot.')->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
