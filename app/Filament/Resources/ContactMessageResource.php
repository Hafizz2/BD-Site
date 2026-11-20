<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static string|UnitEnum|null $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Contact messages';

    public static function getNavigationBadge(): ?string
    {
        $n = ContactMessage::whereNull('read_at')->count();

        return $n ? (string) $n : null;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            TextEntry::make('name')->weight('bold'),
            TextEntry::make('phone')->copyable()->placeholder('—'),
            TextEntry::make('email')->copyable()->placeholder('—'),
            TextEntry::make('subject')->placeholder('—')->columnSpanFull(),
            TextEntry::make('message')->columnSpanFull()->prose(),
            TextEntry::make('created_at')->dateTime(),
            TextEntry::make('handler.name')->label('Handled by')->placeholder('—'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')
            ->columns([
                IconColumn::make('read_at')->label('')->state(fn (ContactMessage $r) => $r->read_at === null)->boolean()
                    ->trueIcon('heroicon-s-envelope')->falseIcon('heroicon-o-envelope-open')->trueColor('gold')->falseColor('gray'),
                TextColumn::make('name')->searchable()->weight(fn (ContactMessage $r) => $r->read_at ? 'semibold' : 'bold')->description(fn (ContactMessage $r) => $r->phone ?: $r->email),
                TextColumn::make('subject')->placeholder('—')->limit(40),
                TextColumn::make('message')->limit(70)->wrap(),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([TernaryFilter::make('read_at')->label('Read')->nullable()])
            ->recordActions([
                ViewAction::make()->after(fn (ContactMessage $r) => $r->read_at ?? $r->forceFill(['read_at' => now(), 'handled_by' => auth()->id()])->save()),
                Action::make('markRead')->label('Mark read')->icon('heroicon-o-check')->visible(fn (ContactMessage $r) => ! $r->read_at)
                    ->action(fn (ContactMessage $r) => $r->forceFill(['read_at' => now(), 'handled_by' => auth()->id()])->save()),
                DeleteAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageContactMessages::route('/')];
    }
}
