<?php

namespace App\Services\Import;

final class StudentFields
{
    /** System field => label */
    public const LABELS = [
        'full_name' => 'Full name',
        'phone' => 'Phone',
        'alt_phone' => 'Alternative phone',
        'gender' => 'Gender',
        'date_of_birth' => 'Date of birth',
        'email' => 'Email',
        'school' => 'School / University',
        'grade_or_year' => 'Grade / Year',
        'sub_city' => 'Sub-city',
        'address' => 'Address',
        'guardian_name' => 'Guardian name',
        'guardian_phone' => 'Guardian phone',
        'guardian_relation' => 'Guardian relation',
        'notes' => 'Notes',
        'registered_at' => 'Timestamp (registered at)',
    ];

    /**
     * Keywords (lower-case, English + Amharic) used for fuzzy header matching.
     * Longer keywords win, so specific phrases beat generic ones.
     */
    public const KEYWORDS = [
        'registered_at' => ['timestamp', 'time stamp', 'submitted at', 'ጊዜ ማህተም', 'የተመዘገበበት'],
        'guardian_relation' => ['relationship', 'relation', 'ዝምድና', 'ግንኙነት'],
        'guardian_phone' => [],
        'guardian_name' => [],
        'alt_phone' => ['alternative phone', 'alternate phone', 'other phone', 'second phone', 'phone 2', 'ሌላ ስልክ', 'ተጨማሪ ስልክ', 'ተለዋጭ ስልክ'],
        'phone' => ['phone number', 'phone', 'telephone', 'mobile', 'tel', 'ስልክ ቁጥር', 'ስልክ', 'ቁጥር'],
        'email' => ['e-mail', 'email', 'ኢሜል', 'ኢሜይል', 'ኢ-ሜይል'],
        'date_of_birth' => ['date of birth', 'birth date', 'birthday', 'dob', 'born', 'የትውልድ ቀን', 'የልደት ቀን', 'ልደት', 'የተወለዱበት'],
        'gender' => ['gender', 'sex', 'ጾታ', 'ፆታ'],
        'sub_city' => ['sub city', 'sub-city', 'subcity', 'kifle ketema', 'woreda', 'ክፍለ ከተማ', 'ወረዳ'],
        'school' => ['university', 'school', 'college', 'institution', 'campus', 'ትምህርት ቤት', 'ዩኒቨርሲቲ', 'ዩኒቨርስቲ', 'ኮሌጅ', 'ተቋም', 'መድረሳ'],
        'grade_or_year' => ['grade', 'year of study', 'class', 'level', 'year', 'ክፍል', 'ዓመት', 'አመት', 'ደረጃ'],
        'address' => ['address', 'where do you live', 'residence', 'neighborhood', 'አድራሻ', 'የሚኖሩበት', 'ሰፈር', 'መኖሪያ'],
        'notes' => ['comment', 'remark', 'note', 'anything else', 'ማስታወሻ', 'አስተያየት', 'ሌላ'],
        'full_name' => ['full name', 'student name', 'your name', 'name', 'ሙሉ ስም', 'የተማሪው ስም', 'ስም'],
    ];

    public const GUARDIAN_KEYWORDS = ['guardian', 'parent', 'father', 'mother', 'ወላጅ', 'አሳዳጊ', 'አባት', 'እናት', 'ቤተሰብ'];

    public static function options(): array
    {
        return self::LABELS;
    }
}
