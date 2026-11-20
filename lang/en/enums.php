<?php

return [
    'PaymentMethod' => [
        'telebirr' => 'Telebirr', 'cbe_birr' => 'CBE Birr', 'cbe' => 'CBE (bank)', 'awash' => 'Awash Bank',
        'abyssinia' => 'Bank of Abyssinia', 'cash' => 'Cash', 'other' => 'Other',
    ],
    'RegistrationStatus' => ['pending' => 'New', 'interview' => 'Interview', 'approved' => 'Accepted (student)', 'waitlisted' => 'Waiting list', 'rejected' => 'Not accepted'],
    'DueStatus' => ['unpaid' => 'Unpaid', 'partially_paid' => 'Partially paid', 'paid' => 'Paid', 'waived' => 'Waived', 'overdue' => 'Overdue'],
    'PaymentStatus' => ['pending_review' => 'Pending review', 'approved' => 'Approved', 'rejected' => 'Rejected'],
    'ProgramType' => [
        'college_training' => 'College training', 'madrasa' => 'Madrasa', 'kids_training' => 'Kids training', 'charity' => 'Charity',
        'event' => 'Event', 'trip' => 'Trip', 'digital_dawah' => "Digital da'wah", 'other' => 'Other',
    ],
    'MemberTeam' => ['dawah' => "Da'wah"],
    'AnnouncementAudience' => [
        'everyone' => 'Everyone', 'all_students' => 'All students', 'all_members' => 'All members', 'program' => 'A program',
        'batch' => 'A batch', 'team' => 'A team', 'unpaid' => 'Everyone with unpaid dues', 'custom' => 'Custom selection',
    ],
    'AnnouncementChannel' => ['telegram_bot' => 'Telegram bot (direct)', 'telegram_channel' => 'Public Telegram channel', 'website' => 'Website'],
    'DuplicateRule' => ['skip' => 'Skip existing students', 'update_empty' => 'Update empty fields only', 'overwrite' => 'Overwrite with sheet values'],
    'StudentSource' => ['web_form' => 'Web form'],
    'FeeAppliesTo' => ['batch' => 'Batch students', 'member_contribution' => 'Member contribution'],
    'FeeFrequency' => ['one_time' => 'One time', 'per_batch' => 'Per batch'],
    'ExpenseCategory' => ['charity_distribution' => 'Charity distribution'],
    'NotificationStatus' => ['blocked_bot' => 'Blocked the bot'],
];
