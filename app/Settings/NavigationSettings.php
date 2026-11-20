<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class NavigationSettings extends Settings
{
    /** [{label,label_am,url,children:[{label,label_am,url}]}] */
    public array $header_links;

    public ?string $header_cta_label;

    public ?string $header_cta_label_am;

    public ?string $header_cta_url;

    /** [{title,title_am,links:[{label,label_am,url}]}] */
    public array $footer_columns;

    public static function group(): string
    {
        return 'navigation';
    }
}
