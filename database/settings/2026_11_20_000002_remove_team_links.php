<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Members of the jemea are confidential — the public Team page was removed, so drop "/team" links from the menus.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $arr = fn ($v) => json_decode(json_encode($v), true) ?? [];
        $keep = fn ($l) => ! in_array(rtrim((string) ($l['url'] ?? ''), '/'), ['/team', '/testimonials'], true);

        $this->migrator->update('navigation.header_links', fn ($links) => array_values(array_map(function ($l) use ($keep) {
            $l['children'] = array_values(array_filter($l['children'] ?? [], $keep));

            return $l;
        }, array_filter($arr($links), $keep))));

        $this->migrator->update('navigation.footer_columns', fn ($cols) => array_map(function ($c) use ($keep) {
            $c['links'] = array_values(array_filter($c['links'] ?? [], $keep));

            return $c;
        }, $arr($cols)));
    }
};
