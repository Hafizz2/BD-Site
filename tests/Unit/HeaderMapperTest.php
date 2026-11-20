<?php

use App\Services\Import\HeaderMapper;

it('auto-maps messy Google Forms headers in Amharic and English', function () {
    $headers = [
        0 => 'Timestamp',
        1 => 'ሙሉ ስም (Full Name)',
        2 => 'ጾታ',
        3 => 'ስልክ ቁጥር',
        4 => 'ሌላ ስልክ ቁጥር (ካለ)',
        5 => 'Email Address',
        6 => 'የትውልድ ቀን',
        7 => 'የሚማሩበት ትምህርት ቤት / ዩኒቨርሲቲ',
        8 => 'ክፍል / ዓመት',
        9 => 'ክፍለ ከተማ',
        10 => 'የወላጅ/አሳዳጊ ስም',
        11 => 'የወላጅ/አሳዳጊ ስልክ',
        12 => 'Anything else you want us to know?',
    ];

    $m = (new HeaderMapper)->guess($headers);

    expect($m)
        ->registered_at->toBe(0)
        ->full_name->toBe(1)
        ->gender->toBe(2)
        ->phone->toBe(3)
        ->alt_phone->toBe(4)
        ->email->toBe(5)
        ->date_of_birth->toBe(6)
        ->school->toBe(7)
        ->grade_or_year->toBe(8)
        ->sub_city->toBe(9)
        ->guardian_name->toBe(10)
        ->guardian_phone->toBe(11)
        ->notes->toBe(12);
});

it('maps plain English headers', function () {
    $m = (new HeaderMapper)->guess(['Name', 'Phone Number', 'Gender', 'Date of Birth', 'University', 'Year', "Parent's phone", 'Relationship to student']);

    expect($m['full_name'])->toBe(0)
        ->and($m['phone'])->toBe(1)
        ->and($m['gender'])->toBe(2)
        ->and($m['date_of_birth'])->toBe(3)
        ->and($m['school'])->toBe(4)
        ->and($m['grade_or_year'])->toBe(5)
        ->and($m['guardian_phone'])->toBe(6)
        ->and($m['guardian_relation'])->toBe(7);
});

it('never maps one column to two fields and leaves unknown fields empty', function () {
    $m = (new HeaderMapper)->guess(['Full name', 'Favourite colour']);

    expect($m['full_name'])->toBe(0)
        ->and($m['phone'])->toBeNull()
        ->and(array_filter($m, fn ($v) => $v === 1))->toBeEmpty();
});
