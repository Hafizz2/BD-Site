<?php

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubmittedVia;
use App\Exports\PaymentsExport;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Due;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Student;
use App\Services\Finance\PaymentService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $n = Payment::where('status', PaymentStatus::PendingReview->value)->count();

        return $n ? (string) $n : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            MorphToSelect::make('payable')->label('Paid by')->types([
                MorphToSelect\Type::make(Student::class)->titleAttribute('full_name'),
                MorphToSelect\Type::make(Member::class)->titleAttribute('full_name'),
            ])->searchable()->required()->live()->columnSpanFull(),
            Select::make('due_id')->label('For due')
                ->options(fn (Get $get) => $get('payable_type') && $get('payable_id')
                    ? Due::where('payable_type', $get('payable_type'))
                        ->where('payable_id', $get('payable_id'))->outstanding()->with('feePlan')->get()
                        ->mapWithKeys(fn (Due $d) => [$d->id => $d->label.' — '.number_format((float) $d->balance(), 2).' ETB'])
                    : [])
                ->searchable(),
            TextInput::make('amount')->required()->numeric()->minValue(0.01)->prefix('ETB'),
            Select::make('method')->options(PaymentMethod::class)->required()->native(false),
            TextInput::make('transaction_ref')->label('Transaction reference')->maxLength(100),
            DateTimePicker::make('paid_at')->default(now())->native(false),
            SpatieMediaLibraryFileUpload::make('receipt')->collection('receipt')->disk('private')->visibility('private')
                ->acceptedFileTypes(['image/*', 'application/pdf'])->maxSize(8192),
            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->schema([
                TextEntry::make('payable.full_name')->label('Paid by')->weight('bold'),
                TextEntry::make('amount')->money('ETB')->weight('bold'),
                TextEntry::make('status')->badge(),
                TextEntry::make('method')->badge()->color('gray'),
                TextEntry::make('due.label')->label('For')->placeholder('General payment'),
                TextEntry::make('transaction_ref')->copyable()->placeholder('—'),
                TextEntry::make('paid_at')->dateTime('d M Y H:i'),
                TextEntry::make('submitted_via')->badge()->color('gray'),
                TextEntry::make('reviewer.name')->label('Reviewed by')->placeholder('—'),
                TextEntry::make('reviewed_at')->dateTime()->placeholder('—'),
                TextEntry::make('rejection_reason')->placeholder('—')->color('danger')->columnSpan(2),
            ]),
            Section::make('Receipt')->schema([
                TextEntry::make('receipt')->hiddenLabel()->state(fn (Payment $r) => self::receiptHtml($r))->html(),
            ]),
        ]);
    }

    public static function receiptHtml(Payment $r): HtmlString
    {
        $media = $r->getFirstMedia('receipt');
        if (! $media) {
            return new HtmlString('<span style="color:#6b7280">No receipt uploaded.</span>');
        }
        $url = $r->receiptUrl();

        return new HtmlString(str_starts_with((string) $media->mime_type, 'image/')
            ? '<a href="'.e($url).'" target="_blank"><img class="ybd-receipt" src="'.e($url).'" alt="Receipt"></a>'
            : '<a href="'.e($url).'" target="_blank" style="font-weight:600;color:#0b3d2a">Open receipt ('.e($media->file_name).') ↗</a>');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['payable', 'due.feePlan', 'media']))
            ->columns([
                TextColumn::make('payable.full_name')->label('Paid by')->weight('semibold')
                    ->searchable(query: fn (Builder $query, string $s) => $query->whereHasMorph('payable', [Student::class, Member::class], fn ($p) => $p->where('full_name', 'like', "%{$s}%")))
                    ->description(fn (Payment $r) => $r->due?->label),
                TextColumn::make('amount')->money('ETB')->sortable()->weight('bold'),
                TextColumn::make('method')->badge()->color('gray'),
                TextColumn::make('transaction_ref')->label('Ref')->searchable()->copyable()->toggleable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('submitted_via')->label('Via')->badge()->color('gray')->toggleable(),
                TextColumn::make('paid_at')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(PaymentStatus::class),
                SelectFilter::make('method')->options(PaymentMethod::class),
                SelectFilter::make('submitted_via')->options(SubmittedVia::class),
                Filter::make('paid_between')->schema([
                    \Filament\Forms\Components\DatePicker::make('from')->native(false),
                    \Filament\Forms\Components\DatePicker::make('until')->native(false),
                ])->query(fn (Builder $query, array $data) => $query
                    ->when($data['from'] ?? null, fn ($q, $v) => $q->whereDate('paid_at', '>=', $v))
                    ->when($data['until'] ?? null, fn ($q, $v) => $q->whereDate('paid_at', '<=', $v))),
            ])
            ->headerActions([
                Action::make('export')->icon('heroicon-o-arrow-down-tray')->color('gray')
                    ->action(fn ($livewire) => Excel::download(new PaymentsExport($livewire->getFilteredTableQuery()), 'payments-'.now()->format('Ymd').'.xlsx')),
            ])
            ->recordActions([
                ActionGroup::make([
                    self::receiptAction(),
                    self::approveAction(),
                    self::rejectAction(),
                    ViewAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('export')->icon('heroicon-o-arrow-down-tray')
                        ->action(fn (Collection $records) => Excel::download(new PaymentsExport(Payment::whereKey($records->modelKeys())), 'payments-selected.xlsx')),
                ]),
            ]);
    }

    public static function receiptAction(): Action
    {
        return Action::make('receipt')->label('Receipt')->icon('heroicon-o-photo')->color('gray')
            ->modalHeading(fn (Payment $r) => 'Receipt — '.$r->payable?->full_name.' · '.number_format((float) $r->amount, 2).' ETB')
            ->modalContent(fn (Payment $r) => self::receiptHtml($r))
            ->modalSubmitAction(false)->modalCancelActionLabel('Close')
            ->extraModalFooterActions(fn (Payment $r) => $r->status === PaymentStatus::PendingReview ? [self::approveAction(), self::rejectAction()] : []);
    }

    public static function approveAction(): Action
    {
        return Action::make('approve')->icon('heroicon-o-check-circle')->color('success')
            ->visible(fn (Payment $r) => $r->status === PaymentStatus::PendingReview && auth()->user()->can('approve_payment'))
            ->requiresConfirmation()
            ->modalDescription(fn (Payment $r) => 'Approve '.number_format((float) $r->amount, 2).' ETB from '.$r->payable?->full_name.'? The due is updated and the payer is notified.')
            ->action(function (Payment $r) {
                $ok = app(PaymentService::class)->approve($r, auth()->user());
                $ok ? Notification::make()->success()->title('Payment approved')->send()
                    : Notification::make()->warning()->title('Already reviewed')->send();
            });
    }

    public static function rejectAction(): Action
    {
        return Action::make('reject')->icon('heroicon-o-x-circle')->color('danger')
            ->visible(fn (Payment $r) => $r->status === PaymentStatus::PendingReview && auth()->user()->can('approve_payment'))
            ->schema([Textarea::make('reason')->required()->maxLength(250)->label('Reason (sent to the payer)')])
            ->action(function (Payment $r, array $data) {
                $ok = app(PaymentService::class)->reject($r, auth()->user(), $data['reason']);
                $ok ? Notification::make()->title('Payment rejected')->send()
                    : Notification::make()->warning()->title('Already reviewed')->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
