<?php

namespace App\Services\Telegram\Exceptions;

/** The user blocked the bot / deactivated / chat not found: stop sending to this chat. */
class BotBlockedException extends TelegramSendException {}
