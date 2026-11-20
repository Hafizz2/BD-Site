<?php

return [
    'PaymentMethod' => [
        'telebirr' => 'ቴሌብር', 'cbe_birr' => 'ሲቢኢ ብር', 'cbe' => 'ኢትዮጵያ ንግድ ባንክ', 'awash' => 'አዋሽ ባንክ',
        'abyssinia' => 'አቢሲኒያ ባንክ', 'cash' => 'ጥሬ ገንዘብ', 'other' => 'ሌላ',
    ],
    'DueStatus' => ['unpaid' => 'ያልተከፈለ', 'partially_paid' => 'በከፊል የተከፈለ', 'paid' => 'የተከፈለ', 'waived' => 'የተሰረዘ', 'overdue' => 'ጊዜው ያለፈ'],
    'PaymentStatus' => ['pending_review' => 'በግምገማ ላይ', 'approved' => 'ጸድቋል', 'rejected' => 'ውድቅ ተደርጓል'],
    'Gender' => ['male' => 'ወንድ', 'female' => 'ሴት'],
    'ProgramType' => [
        'college_training' => 'የኮሌጅ ስልጠና', 'madrasa' => 'መድረሳ', 'kids_training' => 'የልጆች ስልጠና', 'charity' => 'በጎ አድራጎት',
        'event' => 'ዝግጅት', 'trip' => 'ጉዞ', 'digital_dawah' => 'ዲጂታል ዳዕዋ', 'other' => 'ሌላ',
    ],
    'BatchSeason' => ['summer' => 'የክረምት', 'winter' => 'የበጋ', 'regular' => 'መደበኛ'],
];
