<?php

namespace App\Filament\Widgets;

use App\Models\Program;
use App\Models\Student;
use Filament\Widgets\ChartWidget;

class StudentsByProgramChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Students by program';

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = ['default' => 'full', 'xl' => 2];

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Student::class) ?? false;
    }

    protected function getData(): array
    {
        $programs = Program::orderBy('sort')->get()->map(fn (Program $p) => [
            'name' => $p->name,
            'count' => $p->enrollments()->whereIn('enrollments.status', ['approved', 'completed'])->distinct('enrollments.student_id')->count('enrollments.student_id'),
        ])->filter(fn ($p) => $p['count'] > 0)->values();

        return [
            'datasets' => [[
                'data' => $programs->pluck('count')->all(),
                'backgroundColor' => ['#0b3d2a', '#c9a45c', '#3a8a55', '#11553a', '#e2c389', '#5aa874', '#06301f', '#8a6a2c'],
                'borderWidth' => 0,
            ]],
            'labels' => $programs->pluck('name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['position' => 'right']], 'cutout' => '62%', 'scales' => ['x' => ['display' => false], 'y' => ['display' => false]]];
    }
}
