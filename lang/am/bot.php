<?php

return [
    'choose_language' => "እባክዎ ቋንቋ ይምረጡ፦\nPlease choose your language:",
    'language_set' => 'ቋንቋ ወደ አማርኛ ተቀይሯል።',
    'link_success' => '✅ ቴሌግራምዎ በተሳካ ሁኔታ ተገናኝቷል፣ <b>:name</b>። መረጃዎችን እዚህ ይደርስዎታል።',
    'link_invalid' => 'ይህ ሊንክ ትክክል አይደለም ወይም ጊዜው አልፏል። መዝገብዎን እንድናገኝ ስልክ ቁጥርዎን ያጋሩ።',
    'share_contact_prompt' => 'አካውንትዎን ለማገናኘት ከታች ያለውን ቁልፍ ተጭነው ስልክ ቁጥርዎን ያጋሩ።',
    'share_contact_button' => '📱 ስልክ ቁጥሬን አጋራ',
    'contact_not_own' => 'እባክዎ ቁልፉን ተጠቅመው የራስዎን ስልክ ቁጥር ያጋሩ።',
    'contact_not_found' => 'በዚህ ስልክ ቁጥር የተመዘገበ ተማሪ ወይም አባል አላገኘንም። እባክዎ ጀመዓውን ያነጋግሩ።',
    'not_linked' => 'ቴሌግራምዎ ገና አልተገናኘም። ለማገናኘት /start ይላኩ።',
    'help' => "<b>ማድረግ የምችላቸው</b>\n/balance — ያለብዎት ዕዳ እና የቅርብ ክፍያዎች\n/announcements — የቅርብ ማስታወቂያዎች\n/mybatch — ባችዎ እና ቀጣይ ክፍለ ጊዜ\n/language — ቋንቋ ይቀይሩ",

    'balance_title' => '<b>የሂሳብ ሁኔታዎ</b>',
    'balance_none' => '🎉 ምንም ያልተከፈለ ዕዳ የለብዎትም። ጀዛከላሁ ኸይረን!',
    'balance_line' => '• :label — <b>:amount ብር</b> (እስከ :date፣ :status)',
    'balance_total' => 'ጠቅላላ ያልተከፈለ፦ <b>:amount ብር</b>',
    'recent_payments' => '<b>የቅርብ ክፍያዎች</b>',
    'payment_line' => '• :date — :amount ብር በ:method (:status)',

    'payment_approved' => '✅ ለ:label የከፈሉት <b>:amount ብር</b> ደርሷል። ጀዛከላሁ ኸይረን!',
    'payment_rejected' => '❌ ለ:label የከፈሉት <b>:amount ብር</b> ተቀባይነት አላገኘም። ምክንያት፦ :reason። እባክዎ የፋይናንስ ቡድኑን ያነጋግሩ።',

    'reminder_before' => '⏰ ማስታወሻ፦ ለ:label <b>:amount ብር</b> እስከ <b>:date</b> መከፈል አለበት። እባክዎ ለፋይናንስ ቡድኑ ይክፈሉ።',
    'reminder_due' => '📌 ዛሬ የ:label የመክፈያ ቀን ነው — <b>:amount ብር</b>። እባክዎ ለፋይናንስ ቡድኑ ይክፈሉ።',
    'reminder_overdue' => '⚠️ የ:label ክፍያዎ (<b>:amount ብር</b>) እስከ :date ነበር። እባክዎ በተቻለ ፍጥነት ለፋይናንስ ቡድኑ ይክፈሉ።',

    'registration_approved' => '🎉 ለ<b>:batch</b> ያደረጉት ምዝገባ ጸድቋል። እንኳን ደህና መጡ!',
    'registration_rejected' => 'ለ<b>:batch</b> ያደረጉት ምዝገባ አልጸደቀም። :reason',
    'registration_waitlisted' => 'ለ<b>:batch</b> በተጠባባቂ ዝርዝር ውስጥ ነዎት። ቦታ ሲገኝ እናሳውቅዎታለን።',
    'registration_interview' => 'ለ<b>:batch</b> ለቃለ መጠይቅ ተጋብዘዋል። :reason',

    'announcements_title' => '<b>የቅርብ ማስታወቂያዎች</b>',
    'announcements_none' => 'እስካሁን ማስታወቂያ የለም።',

    'mybatch_none' => 'በንቁ ባች ውስጥ አልተመዘገቡም።',
    'mybatch_line' => '📚 <b>:batch</b> (:program)',
    'mybatch_dates' => 'ቀናት፦ :start → :end',
    'mybatch_schedule' => 'መርሃ ግብር፦ :schedule',
    'next_session' => 'ቀጣይ ክፍለ ጊዜ፦ <b>:title</b> — :date:location',
    'no_next_session' => 'እስካሁን የተያዘ ቀጣይ ክፍለ ጊዜ የለም።',
];
