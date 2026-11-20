<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Registration is by link only (each batch has its own link), so the public site no longer
 * points to a general /register page. Everything here stays editable in Admin → Settings.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $swap = fn ($old, $new) => fn ($v) => $v === $old ? $new : $v;

        $this->migrator->update('navigation.header_cta_url', $swap('/register', '/contact'));
        $this->migrator->update('navigation.header_cta_label', $swap('Register', 'Contact us'));
        $this->migrator->update('navigation.header_cta_label_am', $swap('ይመዝገቡ', 'ያግኙን'));

        $this->migrator->update('homepage.hero_primary_url', $swap('/register', '/programs'));
        $this->migrator->update('homepage.hero_primary_label', $swap('Join a program', 'Our programs'));
        $this->migrator->update('homepage.hero_primary_label_am', $swap('ፕሮግራም ይቀላቀሉ', 'ፕሮግራሞቻችን'));

        $this->migrator->update('homepage.cta_primary_url', $swap('/register', '/contact'));
        $this->migrator->update('homepage.cta_primary_label', $swap('Register now', 'Contact us'));
        $this->migrator->update('homepage.cta_primary_label_am', $swap('አሁን ይመዝገቡ', 'ያግኙን'));
        $this->migrator->update('homepage.cta_text', $swap(
            'Registration is open for the next batch. Join as a student, volunteer or supporter.',
            'New course registrations are announced on our Telegram channel and this website.'
        ));
        $this->migrator->update('homepage.cta_text_am', $swap(
            'ለቀጣዩ ባች ምዝገባ ተከፍቷል። እንደ ተማሪ፣ በጎ ፈቃደኛ ወይም ደጋፊ ይቀላቀሉ።',
            'አዳዲስ የኮርስ ምዝገባዎች በቴሌግራም ቻናላችንና በዚህ ድረ-ገጽ ይገለጻሉ።'
        ));

        // Drop "/register" links from the header and footer menus (stored JSON may decode as objects).
        $arr = fn ($v) => json_decode(json_encode($v), true) ?? [];
        $keep = fn ($l) => ($l['url'] ?? null) !== '/register';

        $this->migrator->update('navigation.header_links', fn ($links) => array_values(array_filter($arr($links), $keep)));
        $this->migrator->update('navigation.footer_columns', fn ($cols) => array_map(function ($c) use ($keep) {
            $c['links'] = array_values(array_filter($c['links'] ?? [], $keep));

            return $c;
        }, $arr($cols)));
    }
};
