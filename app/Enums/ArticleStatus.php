<?php

namespace App\Enums;

use App\Enums\Concerns\EnumHelpers;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ArticleStatus: string implements HasColor, HasLabel
{
    use EnumHelpers;

    case Draft = 'draft';
    case Published = 'published';

    public function getColor(): string|array|null
    {
        return match ($this->value) {
            'draft' => 'gray', 'published' => 'success',
            default => 'gray',
        };
    }
}
