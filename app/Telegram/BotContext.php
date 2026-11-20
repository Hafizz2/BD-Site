<?php

namespace App\Telegram;

use App\Models\Member;
use App\Models\Student;
use App\Services\Telegram\TelegramLinker;
use Illuminate\Support\Facades\Cache;
use SergiX44\Nutgram\Nutgram;

/**
 * Helpers shared by bot handlers: who is talking, and in which language.
 */
class BotContext
{
    public static function userId(Nutgram $bot): ?string
    {
        $id = $bot->userId();

        return $id ? (string) $id : null;
    }

    /** @return array{student:?Student,member:?Member} */
    public static function people(Nutgram $bot): array
    {
        $id = self::userId($bot);

        return $id ? app(TelegramLinker::class)->resolve($id) : ['student' => null, 'member' => null];
    }

    /** The primary payer for this chat: the student record first, else the member record. */
    public static function person(Nutgram $bot): Student|Member|null
    {
        $p = self::people($bot);

        return $p['student'] ?? $p['member'];
    }

    public static function locale(Nutgram $bot): string
    {
        $id = self::userId($bot);
        $cached = $id ? Cache::get("tg_lang:{$id}") : null;
        if ($cached) {
            return $cached;
        }

        return self::person($bot)?->locale ?: 'am';
    }

    public static function setLocale(Nutgram $bot, string $locale): void
    {
        $locale = in_array($locale, ['am', 'en'], true) ? $locale : 'am';
        if ($id = self::userId($bot)) {
            Cache::forever("tg_lang:{$id}", $locale);
        }
        foreach (self::people($bot) as $p) {
            $p?->forceFill(['locale' => $locale])->save();
        }
        app()->setLocale($locale);
    }

    public static function hasChosenLanguage(Nutgram $bot): bool
    {
        $id = self::userId($bot);

        return $id && Cache::has("tg_lang:{$id}");
    }

    public static function isPrivate(Nutgram $bot): bool
    {
        return $bot->chat()?->type?->value === 'private' || $bot->chat()?->type === 'private';
    }
}
