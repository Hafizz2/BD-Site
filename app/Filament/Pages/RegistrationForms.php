<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Settings\ManageRegistration;
use App\Filament\Resources\BatchResource;
use App\Models\Batch;
use App\Support\RegistrationForm;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Every batch's registration form in one place: open/close it, edit it, preview it, copy its link.
 */
class RegistrationForms extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.registration-forms';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Registration forms';

    protected static ?string $title = 'Registration forms';

    protected ?string $subheading = 'Each batch has its own form and link. Switch a link on when you want people to register.';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', Batch::class) && auth()->user()->can('update', new Batch);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Batch::query()->with('program')->withCount('registrations'))
            ->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('form_title')->label('Form')->weight('semibold')
                    ->state(fn (Batch $r) => RegistrationForm::header($r, 'en')['title'])
                    ->description(fn (Batch $r) => $r->label)
                    ->searchable(query: fn ($query, string $search) => $query->where('name', 'like', "%{$search}%")
                        ->orWhereHas('program', fn ($p) => $p->where('name', 'like', "%{$search}%"))),
                ToggleColumn::make('registration_open')->label('Link open')
                    ->tooltip('When on, people with the link can register'),
                TextColumn::make('questions')->label('Questions')
                    ->state(fn (Batch $r) => count(array_filter(RegistrationForm::enabled($r), fn ($f) => $f['type'] !== 'section')))
                    ->badge()->color('gray'),
                TextColumn::make('registrations_count')->label('Registrations')->badge()->color('warning')
                    ->url(fn (Batch $r) => BatchResource::getUrl('view', ['record' => $r])),
                TextColumn::make('start_date')->label('Starts')->date('d M Y')->sortable()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('registration_open')->label('Link')->options(['1' => 'Open', '0' => 'Closed']),
                SelectFilter::make('program_id')->label('Program')->relationship('program', 'name'),
            ])
            ->recordActions([
                Action::make('editForm')->label('Edit form')->icon('heroicon-o-pencil-square')->color('primary')->button()
                    ->url(fn (Batch $r) => BatchResource::getUrl('edit', ['record' => $r, 'tab' => 'form'])),
                Action::make('preview')->label('Preview')->icon('heroicon-o-eye')->color('gray')
                    ->url(fn (Batch $r) => route('register.batch', ['batch' => $r, 'preview' => 1]))->openUrlInNewTab(),
                BatchResource::registrationLinkAction()->label('Copy link'),
            ])
            ->recordUrl(fn (Batch $r) => BatchResource::getUrl('edit', ['record' => $r, 'tab' => 'form']))
            ->emptyStateHeading('No batches yet')
            ->emptyStateDescription('Create a batch first — every batch gets its own registration form.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newBatch')->label('New batch')->icon('heroicon-o-plus')
                ->url(BatchResource::getUrl('create'))
                ->visible(fn () => auth()->user()->can('create', Batch::class)),
            Action::make('defaults')->label('Default messages')->icon('heroicon-o-cog-6-tooth')->color('gray')
                ->tooltip('“Registration closed” message, default thank-you message, spam protection')
                ->url(ManageRegistration::getUrl()),
        ];
    }
}
