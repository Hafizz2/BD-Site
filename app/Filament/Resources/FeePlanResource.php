<?php

namespace App\Filament\Resources;

use App\Enums\FeeAppliesTo;
use App\Enums\FeeFrequency;
use App\Filament\Resources\FeePlanResource\Pages;
use App\Models\FeePlan;
use App\Services\Finance\DuesGenerator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class FeePlanResource extends Resource
{
    protected static ?string $model = FeePlan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 1;

    public static function formSchema(bool $forBatch = false): array
    {
        return array_values(array_filter([
            TextInput::make('name')->required()->maxLength(120),
            TextInput::make('amount')->required()->numeric()->minValue(0.01)->step('0.01')->prefix('ETB'),
            Select::make('frequency')->options(FeeFrequency::class)->required()->live()->native(false),
            $forBatch ? null : Select::make('applies_to')->options(FeeAppliesTo::class)->required()->live()->native(false)
                ->helperText('Batch students, or a member contribution assigned on each member.'),
            $forBatch ? null : Select::make('batch_id')->relationship('batch', 'name', fn ($query) => $query->with('program'))
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->label)->searchable()->preload()
                ->visible(fn (Get $get) => $get('applies_to') === FeeAppliesTo::Batch->value || $get('applies_to') === FeeAppliesTo::Batch)
                ->required(fn (Get $get) => $get('applies_to') === FeeAppliesTo::Batch->value || $get('applies_to') === FeeAppliesTo::Batch),
            TextInput::make('due_day')->label('Due day of month')->numeric()->minValue(1)->maxValue(28)->default(10)
                ->visible(fn (Get $get) => in_array($get('frequency'), [FeeFrequency::Monthly->value, FeeFrequency::Monthly], true)),
            TextInput::make('due_in_days')->label('Due … days after enrollment approval')->numeric()->minValue(0)->default(14)
                ->visible(fn (Get $get) => ! in_array($get('frequency'), [FeeFrequency::Monthly->value, FeeFrequency::Monthly], true)),
            Toggle::make('is_active')->default(true)->inline(false),
            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components(self::formSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->weight('semibold')->description(fn (FeePlan $r) => $r->batch?->label),
                TextColumn::make('amount')->money('ETB')->sortable(),
                TextColumn::make('frequency')->badge(),
                TextColumn::make('applies_to')->badge()->color('gray'),
                TextColumn::make('dues_count')->counts('dues')->label('Dues'),
                ToggleColumn::make('is_active')->label('Active'),
            ])
            ->filters([SelectFilter::make('frequency')->options(FeeFrequency::class), SelectFilter::make('applies_to')->options(FeeAppliesTo::class)])
            ->recordActions([
                Action::make('generate')->label('Generate dues')->icon('heroicon-o-bolt')->color('gold')->requiresConfirmation()
                    ->modalDescription('Idempotent — running it again never creates duplicates.')
                    ->action(function (FeePlan $r) {
                        $n = $r->frequency === FeeFrequency::Monthly ? app(DuesGenerator::class)->generateMonthly() : app(DuesGenerator::class)->forPlan($r);
                        Notification::make()->success()->title("{$n} dues created")->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageFeePlans::route('/')];
    }
}
