<?php

namespace App\Enums\Concerns;

use Illuminate\Support\Str;

trait EnumHelpers
{
    public function getLabel(): ?string
    {
        $key = 'enums.'.class_basename(static::class).'.'.$this->value;
        $t = __($key);

        return $t !== $key ? $t : Str::headline($this->value);
    }

    /** @return array<string,string> */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->getLabel();
        }

        return $out;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn ($c) => $c->value, self::cases());
    }
}
