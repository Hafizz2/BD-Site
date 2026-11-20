<?php

use App\Support\PhoneNumber;

it('normalizes Ethiopian mobile numbers to E.164', function (mixed $input, ?string $expected) {
    expect(PhoneNumber::normalize($input))->toBe($expected);
})->with([
    'local 09' => ['0911234567', '+251911234567'],
    'local 07 (Safaricom)' => ['0711234567', '+251711234567'],
    'no leading zero' => ['911234567', '+251911234567'],
    'plus country code' => ['+251911234567', '+251911234567'],
    'country code no plus' => ['251911234567', '+251911234567'],
    '00 prefix' => ['00251911234567', '+251911234567'],
    'spaces' => ['0911 23 45 67', '+251911234567'],
    'dashes' => ['091-123-4567', '+251911234567'],
    'plus with spaces' => ['+251 91 123 4567', '+251911234567'],
    'excel number lost zero' => [911234567, '+251911234567'],
    'excel float' => [911234567.0, '+251911234567'],
    'parentheses' => ['(+251) 911-234-567', '+251911234567'],
    'landline rejected' => ['0111234567', null],
    'too short' => ['091123456', null],
    'too long' => ['09112345678', null],
    'foreign number' => ['+14155552671', null],
    'letters' => ['phone', null],
    'empty' => ['', null],
    'null' => [null, null],
]);

it('formats numbers for display', function () {
    expect(PhoneNumber::format('+251911234567'))->toBe('+251 91 123 4567');
});
