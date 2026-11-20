<?php

namespace App\Filament\Resources;

use App\Enums\FeeAppliesTo;
use App\Enums\MemberTeam;
use App\Filament\Actions\TelegramActions;
use App\Filament\Resources\MemberResource\Pages;
use App\Filament\Support\Bilingual;
use App\Models\Member;
use App\Support\PhoneNumber;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static string|UnitEnum|null $navigationGroup = 'People';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Members / Team';

    protected static ?string $recordTitleAttribute = 'full_name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Member')->schema([
                Bilingual::text('full_name', 'Full name', true, 150),
                Bilingual::text('role_title', 'Role / title (e.g. "Finance Head")', false, 100),
                Grid::make(['default' => 1, 'md' => 3])->schema([
                    Select::make('team')->options(MemberTeam::class)->native(false),
                    TextInput::make('phone')->tel()->maxLength(20)->unique(ignoreRecord: true)
                        ->dehydrateStateUsing(fn ($state) => PhoneNumber::normalize($state) ?? $state),
                    TextInput::make('email')->email()->maxLength(150),
                    Select::make('user_id')->label('Admin login (user)')->relationship('user', 'email')->searchable()->preload()
                        ->helperText('Link a user if this member also uses the admin panel.'),
                    Select::make('student_id')->label('Also a student')->relationship('student', 'full_name')->searchable(),
                    Select::make('monthly_contribution_plan_id')->label('Monthly contribution (only if they agreed to give)')->visible(fn () => self::canManageContributions())->placeholder('Does not contribute')
                        ->relationship('contributionPlan', 'name', fn ($query) => $query->where('applies_to', FeeAppliesTo::MemberContribution->value)),
                ])->columnSpanFull(),
                Grid::make(['default' => 1, 'md' => 3])->schema([
                    Toggle::make('is_active')->default(true),
                    Select::make('locale')->label('Bot language')->options(['am' => 'አማርኛ', 'en' => 'English'])->default('am')->native(false),
                ])->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort')
            // One card per person (resources/views/filament/cards/member.blade.php).
            ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->with(['contributionPlan', 'media']))
            ->columns([
                \Filament\Tables\Columns\Layout\View::make('filament.cards.member'),
            ])
            ->contentGrid(['md' => 2, '2xl' => 3])
            ->searchable(['full_name', 'full_name_am', 'phone'])
            ->searchPlaceholder('Search name or phone')
            ->recordUrl(null)
            ->filters([
                SelectFilter::make('team')->options(MemberTeam::class),
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                ActionGroup::make([EditAction::make(), TelegramActions::copyLink(), TelegramActions::qr(), TelegramActions::message(), DeleteAction::make()]),
            ])
            ->toolbarActions([BulkActionGroup::make([
                \Filament\Actions\BulkAction::make('addContributors')->label('Add to contributors')->visible(fn () => self::canManageContributions())->icon('heroicon-o-banknotes')->color('gold')
                    ->schema([
                        Select::make('plan')->label('Monthly contribution plan')->required()
                            ->options(fn () => \App\Models\FeePlan::where('applies_to', FeeAppliesTo::MemberContribution->value)->pluck('name', 'id')),
                    ])
                    ->action(fn (\Illuminate\Support\Collection $records, array $data) => $records->each->update(['monthly_contribution_plan_id' => $data['plan']]))
                    ->deselectRecordsAfterCompletion(),
                \Filament\Actions\BulkAction::make('removeContributors')->label('Remove from contributors')->visible(fn () => self::canManageContributions())->icon('heroicon-o-minus-circle')
                    ->requiresConfirmation()->modalDescription('They stay members; no new monthly dues will be created for them.')
                    ->action(fn (\Illuminate\Support\Collection $records) => $records->each->update(['monthly_contribution_plan_id' => null]))
                    ->deselectRecordsAfterCompletion(),
                TelegramActions::bulkMessage(),
                DeleteBulkAction::make(),
            ])]);
    }

    /** Who pays is a finance decision: finance, coordinators and super admin only (not media). */
    public static function canManageContributions(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin', 'finance']) ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
        ];
    }
}
