<?php

namespace App\Filament\Resources\BatchResource\RelationManagers;

use App\Enums\FeeAppliesTo;
use App\Enums\FeeFrequency;
use App\Filament\Resources\FeePlanResource;
use App\Models\FeePlan;
use App\Services\Finance\DuesGenerator;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FeePlansRelationManager extends RelationManager
{
    protected static string $relationship = 'feePlans';

    protected static ?string $title = 'Fees';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('viewAny', FeePlan::class) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(FeePlanResource::formSchema(forBatch: true));
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->weight('semibold'),
                TextColumn::make('amount')->money('ETB'),
                TextColumn::make('frequency')->badge(),
                TextColumn::make('dues_count')->counts('dues')->label('Dues'),
                IconColumn::make('is_active')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->mutateDataUsing(fn (array $data) => $data + ['applies_to' => FeeAppliesTo::Batch->value]),
            ])
            ->recordActions([
                Action::make('generate')->label('Generate dues now')->icon('heroicon-o-bolt')->color('gold')->requiresConfirmation()
                    ->modalDescription('Creates the dues for every approved student in this batch. Safe to run again — existing dues are never duplicated.')
                    ->action(function (FeePlan $record) {
                        $n = $record->frequency === FeeFrequency::Monthly
                            ? app(DuesGenerator::class)->generateMonthly()
                            : app(DuesGenerator::class)->forPlan($record);
                        Notification::make()->success()->title("{$n} dues created")->send();
                    }),
                EditAction::make(),
            ]);
    }
}
