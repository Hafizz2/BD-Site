<?php

namespace App\Filament\Resources\BatchResource\RelationManagers;

use App\Enums\EnrollmentStatus;
use App\Exports\StudentsExport;
use App\Filament\Resources\StudentResource;
use App\Models\Enrollment;
use App\Services\CertificateService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    protected static ?string $title = 'Students';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('student_id')->relationship('student', 'full_name')->searchable(['full_name', 'phone'])->required()
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name.' · '.$r->phone),
            Select::make('status')->options(EnrollmentStatus::class)->default(EnrollmentStatus::Approved)->required()->native(false),
            DateTimePicker::make('enrolled_at')->default(now())->native(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('student'))
            ->columns([
                TextColumn::make('student.full_name')->label('Student')->searchable()->weight('semibold')
                    ->url(fn (Enrollment $r) => $r->student ? StudentResource::getUrl('view', ['record' => $r->student]) : null),
                TextColumn::make('student.phone')->label('Phone')->searchable(),
                TextColumn::make('student.gender')->label('Gender')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('enrolled_at')->date('d M Y')->sortable(),
                TextColumn::make('certificate_number')->label('Certificate')->placeholder('—')->toggleable(),
            ])
            ->filters([SelectFilter::make('status')->options(EnrollmentStatus::class)])
            ->headerActions([
                CreateAction::make()->label('Add student'),
                Action::make('export')->icon('heroicon-o-arrow-down-tray')->color('gray')
                    ->action(fn () => Excel::download(new StudentsExport(
                        \App\Models\Student::whereHas('enrollments', fn ($e) => $e->where('batch_id', $this->getOwnerRecord()->id))
                    ), 'batch-'.$this->getOwnerRecord()->id.'-students.xlsx')),
            ])
            ->recordActions([
                Action::make('certificate')->icon('heroicon-o-document-arrow-down')->color('gold')
                    ->visible(fn (Enrollment $r) => $r->status === EnrollmentStatus::Completed)
                    ->action(fn (Enrollment $r) => app(CertificateService::class)->download($r)),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('complete')->label('Mark completed & issue certificates')->icon('heroicon-o-academic-cap')->requiresConfirmation()
                        ->action(function (Collection $records) {
                            $records->each(fn (Enrollment $e) => app(CertificateService::class)->issue($e));
                        })->deselectRecordsAfterCompletion(),
                    BulkAction::make('drop')->label('Mark dropped')->color('danger')->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['status' => EnrollmentStatus::Dropped]))->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
