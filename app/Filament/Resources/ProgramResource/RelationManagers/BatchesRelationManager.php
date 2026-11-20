<?php

namespace App\Filament\Resources\ProgramResource\RelationManagers;

use App\Filament\Resources\BatchResource;
use App\Models\Batch;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class BatchesRelationManager extends RelationManager
{
    protected static string $relationship = 'batches';

    public function form(Schema $schema): Schema
    {
        return $schema->components(BatchResource::formSchema(withProgram: false));
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('name')->weight('semibold'),
                TextColumn::make('season')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('start_date')->date('d M Y'),
                TextColumn::make('end_date')->date('d M Y'),
                TextColumn::make('enrollments_count')->counts('enrollments')->label('Enrolled'),
                ToggleColumn::make('registration_open')->label('Reg. open'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                Action::make('open')->icon('heroicon-o-eye')->url(fn (Batch $r) => BatchResource::getUrl('view', ['record' => $r])),
                EditAction::make(),
            ]);
    }
}
