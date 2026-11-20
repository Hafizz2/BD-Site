<?php

namespace App\Services\Telegram;

use App\Models\Member;
use App\Models\Registration;
use App\Models\Student;
use App\Models\TelegramLinkToken;
use App\Settings\BotSettings;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TelegramLinker
{
    public const TOKEN_TTL_DAYS = 7;

    public function __construct(private readonly BotSettings $settings) {}

    /** Returns a usable token for the person, creating one if needed. */
    public function tokenFor(Student|Member|Registration $person): TelegramLinkToken
    {
        $existing = $person->telegramLinkTokens()
            ->whereNull('used_at')
            ->where('expires_at', '>', now()->addDay())
            ->latest('id')
            ->first();

        return $existing ?? $person->telegramLinkTokens()->create([
            'token' => Str::random(32),
            'expires_at' => now()->addDays(self::TOKEN_TTL_DAYS),
        ]);
    }

    public function deepLinkFor(Student|Member|Registration $person): ?string
    {
        $username = ltrim((string) $this->settings->bot_username, '@');
        if ($username === '') {
            return null;
        }

        return "https://t.me/{$username}?start=".$this->tokenFor($person)->token;
    }

    /**
     * Link via deep-link token. Returns the linked person, or null when the token is invalid/used/expired.
     */
    public function linkByToken(string $token, string|int $chatId): ?Model
    {
        return DB::transaction(function () use ($token, $chatId) {
            $row = TelegramLinkToken::where('token', $token)->lockForUpdate()->first();
            if (! $row || ! $row->isUsable() || ! $row->linkable) {
                return null;
            }

            $row->forceFill(['used_at' => now()])->save();
            $this->detachChatFromOthers((string) $chatId, $row->linkable->phone);
            $row->linkable->linkTelegram($chatId);

            // A pending web registration: also link any existing student with the same phone,
            // and the student created on approval inherits the chat id.
            if ($row->linkable instanceof Registration) {
                $student = $row->linkable->student ?? $row->linkable->matchingStudent();
                $student?->linkTelegram($chatId);
            }

            return $row->linkable;
        });
    }

    /**
     * Link via a shared Telegram contact. Students take priority, then members — both are linked if both match.
     *
     * @return list<Model>
     */
    public function linkByPhone(string $rawPhone, string|int $chatId): array
    {
        $phone = PhoneNumber::normalize($rawPhone);
        if (! $phone) {
            return [];
        }

        $this->detachChatFromOthers((string) $chatId, $phone);
        $linked = [];
        foreach ([Student::class, Member::class] as $cls) {
            $person = $cls::where('phone', $phone)->first();
            if ($person) {
                $person->linkTelegram($chatId);
                $linked[] = $person;
            }
        }

        return $linked;
    }

    /**
     * Find the people linked to a chat (a person may be both a student and a member).
     *
     * @return array{student:?Student,member:?Member}
     */
    public function resolve(string|int $chatId): array
    {
        $chatId = (string) $chatId;

        return [
            'student' => Student::where('telegram_chat_id', $chatId)->first(),
            'member' => Member::where('telegram_chat_id', $chatId)->first(),
        ];
    }

    /**
     * One Telegram chat belongs to one human; clear stale links held by other people
     * (records with the same phone are the same human — e.g. a student who is also a member).
     */
    private function detachChatFromOthers(string $chatId, ?string $keepPhone = null): void
    {
        foreach ([Student::class, Member::class] as $cls) {
            $cls::where('telegram_chat_id', $chatId)
                ->when($keepPhone, fn ($q) => $q->where(fn ($w) => $w->whereNull('phone')->orWhere('phone', '!=', $keepPhone)))
                ->update(['telegram_chat_id' => null, 'telegram_linked_at' => null]);
        }
    }
}
