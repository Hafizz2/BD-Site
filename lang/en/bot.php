<?php

return [
    'choose_language' => "Please choose your language:\nእባክዎ ቋንቋ ይምረጡ፦",
    'language_set' => 'Language set to English.',
    'link_success' => '✅ Your Telegram is now linked, <b>:name</b>. You will receive updates here.',
    'link_invalid' => 'This link is invalid or has expired. Please share your phone number so we can find your record.',
    'share_contact_prompt' => 'To link your account, tap the button below to share your phone number.',
    'share_contact_button' => '📱 Share my phone number',
    'contact_not_own' => 'Please share your own contact using the button.',
    'contact_not_found' => "We couldn't find a student or member with this phone number. Please contact the jemea so we can add you.",
    'not_linked' => 'Your Telegram is not linked yet. Send /start to link your account.',
    'help' => "<b>What I can do</b>\n/balance — what you owe and recent payments\n/announcements — latest announcements\n/mybatch — your batch and schedule\n/language — change language",

    'balance_title' => '<b>Your balance</b>',
    'balance_none' => '🎉 You have no outstanding dues. Jazakallahu khairan!',
    'balance_line' => '• :label — <b>:amount ETB</b> (due :date, :status)',
    'balance_total' => 'Total outstanding: <b>:amount ETB</b>',
    'recent_payments' => '<b>Recent payments</b>',
    'payment_line' => '• :date — :amount ETB via :method (:status)',

    'payment_approved' => '✅ Your payment of <b>:amount ETB</b> for :label was received. Jazakallahu khairan!',
    'payment_rejected' => '❌ Your payment of <b>:amount ETB</b> for :label was not accepted. Reason: :reason. Please contact the finance team.',

    'reminder_before' => '⏰ Reminder: <b>:amount ETB</b> for :label is due on <b>:date</b>. Please pay the finance team.',
    'reminder_due' => '📌 Today is the due date for :label — <b>:amount ETB</b>. Please pay the finance team.',
    'reminder_overdue' => '⚠️ Your payment for :label (<b>:amount ETB</b>) was due on :date. Please pay the finance team as soon as possible.',

    'registration_approved' => '🎉 Your registration for <b>:batch</b> has been approved. Welcome!',
    'registration_rejected' => 'Your registration for <b>:batch</b> was not approved. :reason',
    'registration_waitlisted' => 'You are on the waiting list for <b>:batch</b>. We will notify you if a place opens.',
    'registration_interview' => 'You are invited to an interview for <b>:batch</b>. :reason',

    'announcements_title' => '<b>Latest announcements</b>',
    'announcements_none' => 'No announcements yet.',

    'mybatch_none' => 'You are not enrolled in an active batch.',
    'mybatch_line' => '📚 <b>:batch</b> (:program)',
    'mybatch_dates' => 'Dates: :start → :end',
    'mybatch_schedule' => 'Schedule: :schedule',
    'next_session' => 'Next session: <b>:title</b> — :date:location',
    'no_next_session' => 'No upcoming session scheduled yet.',
];
