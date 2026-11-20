<?php

namespace App\Filament\Resources\StudentResource\RelationManagers;

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;
use App\Services\CertificateService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Enrollments';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-academic-cap';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('batch_id')->relationship('batch', 'name', fn ($query) => $query->with('program'))
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->label)->searchable()->preload()->required(),
            Select::make('status')->options(EnrollmentStatus::class)->default(EnrollmentStatus::Approved)->required()->native(false),
            DateTimePicker::make('enrolled_at')->default(now())->native(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('enrolled_at', 'desc')
            ->columns([
                TextColumn::make('batch.program.name')->label('Program'),
                TextColumn::make('batch.name')->label('Batch')->weight('semibold'),
                TextColumn::make('status')->badge(),
                TextColumn::make('enrolled_at')->date('d M Y'),
                TextColumn::make('certificate_number')->label('Certificate')->placeholder('—'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                Action::make('certificate')->icon('heroicon-o-document-arrow-down')->color('gold')
                    ->visible(fn (Enrollment $r) => $r->status === EnrollmentStatus::Completed)
                    ->action(fn (Enrollment $r) => app(CertificateService::class)->download($r)),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
