<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    public function getColumns(): int|array
    {
        return ['default' => 1, 'md' => 2, 'xl' => 4];
    }

    public function getSubheading(): ?string
    {
        $hour = (int) now('Africa/Addis_Ababa')->format('H');
        $greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

        return 'Assalamu alaikum, '.auth()->user()?->name.' — '.$greet.'.';
    }
}
