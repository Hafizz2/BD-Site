<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // ---------------- General ----------------
        $g = [
            'site_name' => 'Ye Birhan Dana',
            'site_name_am' => 'የብርሃን ዳና',
            'tagline' => 'Walking the path of light together',
            'tagline_am' => 'በብርሃን መንገድ አብረን እንጓዝ',
            'meta_description' => "Ye Birhan Dana jemea — comparative religion training, madrasa, charity, da'wah programs and trips. Students of EIADE.",
            'meta_description_am' => 'የብርሃን ዳና ጀመዓ — የንጽጽር ሃይማኖት ስልጠና፣ መድረሳ፣ በጎ አድራጎት፣ የዳዕዋ ፕሮግራሞችና ጉዞዎች።',
            'footer_about' => "A jemea of EIADE students serving the ummah through knowledge, da'wah and charity. Addis Ababa, Ethiopia.",
            'footer_about_am' => 'በእውቀት፣ በዳዕዋ እና በበጎ አድራጎት ኡማውን የሚያገለግል የEIADE ተማሪዎች ጀመዓ። አዲስ አበባ፣ ኢትዮጵያ።',
            'phone' => '+251 9XX XXX XXX',
            'phone_secondary' => null,
            'email' => 'info@yebirhandana.org',
            'address' => 'Addis Ababa, Ethiopia',
            'address_am' => 'አዲስ አበባ፣ ኢትዮጵያ',
            'office_hours' => 'Sat – Thu, 9:00 – 17:00',
            'office_hours_am' => 'ቅዳሜ – ሐሙስ፣ 3:00 – 11:00',
            'map_embed_url' => null,
            'telegram_url' => 'https://t.me/yebirhandana',
            'facebook_url' => null,
            'youtube_url' => null,
            'tiktok_url' => null,
            'instagram_url' => null,
            'logo_path' => null,
            'og_image_path' => null,
            'verse_arabic' => 'ادْعُ إِلَىٰ سَبِيلِ رَبِّكَ بِالْحِكْمَةِ وَالْمَوْعِظَةِ الْحَسَنَةِ',
            'verse_translation' => '"Invite to the way of your Lord with wisdom and good instruction."',
            'verse_translation_am' => '"ወደ ጌታህ መንገድ በጥበብና በመልካም ግሳጼ ጥራ።"',
            'verse_reference' => 'Surah An-Nahl · 16:125',
            'verse_reference_am' => 'ሱረቱ አን-ነሕል · 16:125',
            'copyright' => 'Ye Birhan Dana · የብርሃን ዳና — All rights reserved.',
            'copyright_am' => 'የብርሃን ዳና — መብቱ በሕግ የተጠበቀ ነው።',
        ];
        foreach ($g as $k => $v) {
            $this->migrator->add("general.$k", $v);
        }

        // ---------------- Homepage ----------------
        $section = fn (string $eyebrow, string $eyebrowAm, string $title, string $titleAm, ?string $intro = null, ?string $introAm = null, int $limit = 6, bool $enabled = true) => [
            'enabled' => $enabled, 'eyebrow' => $eyebrow, 'eyebrow_am' => $eyebrowAm,
            'title' => $title, 'title_am' => $titleAm, 'intro' => $intro, 'intro_am' => $introAm, 'limit' => $limit,
        ];

        $h = [
            'hero_eyebrow' => 'Students of EIADE',
            'hero_eyebrow_am' => 'የEIADE ተማሪዎች',
            'hero_title' => 'Ye Birhan',
            'hero_title_highlight' => 'Dana',
            'hero_title_am' => 'የብርሃን ዳና',
            'hero_lead' => "Walking the path of light together. We teach comparative knowledge, train the next generation of du'at, and serve our community through education, charity and da'wah.",
            'hero_lead_am' => 'በብርሃን መንገድ አብረን እንጓዛለን። የንጽጽር እውቀትን እናስተምራለን፣ ቀጣዩን የዱዓት ትውልድ እናሰለጥናለን፣ ማህበረሰባችንን በትምህርት፣ በበጎ አድራጎት እና በዳዕዋ እናገለግላለን።',
            'hero_primary_label' => 'Join a program',
            'hero_primary_label_am' => 'ፕሮግራም ይቀላቀሉ',
            'hero_primary_url' => '/register',
            'hero_secondary_label' => 'Explore programs',
            'hero_secondary_label_am' => 'ፕሮግራሞችን ይመልከቱ',
            'hero_secondary_url' => '/services',
            'show_stats' => true,
            'show_verse' => true,
            'about_cards' => [
                ['title' => 'Our Mission', 'title_am' => 'ተልዕኳችን', 'text' => 'To equip Muslims of every age with sound knowledge and confident answers, and to share the message of Islam with wisdom.', 'text_am' => 'በሁሉም እድሜ ያሉ ሙስሊሞችን በትክክለኛ እውቀትና በእርግጠኛ መልስ ማስታጠቅ፣ የኢስላምን መልእክት በጥበብ ማድረስ።'],
                ['title' => 'Our Vision', 'title_am' => 'ራዕያችን', 'text' => 'A generation that knows its deen deeply, understands other worldviews fairly, and lights the path for others.', 'text_am' => 'ዲኑን በጥልቀት የሚያውቅ፣ ሌሎች አመለካከቶችን በፍትሃዊነት የሚረዳ እና ለሌሎች መንገድ የሚያበራ ትውልድ።'],
                ['title' => 'Our Values', 'title_am' => 'እሴቶቻችን', 'text' => "Sincerity, knowledge, wisdom and service — guided by the Qur'an and Sunnah.", 'text_am' => 'ኢኽላስ፣ እውቀት፣ ጥበብ እና አገልግሎት — በቁርኣንና በሱና የተመሩ።'],
            ],
            'pillars' => [
                ['label' => 'Ikhlas', 'label_am' => 'ኢኽላስ'],
                ['label' => 'Ilm', 'label_am' => 'ዒልም'],
                ['label' => 'Hikmah', 'label_am' => 'ሂክማ'],
                ['label' => 'Khidma', 'label_am' => 'ኺድማ'],
            ],
            'sections' => [
                'about' => $section('Who we are', 'እኛ ማን ነን', 'A jemea built on knowledge & service', 'በእውቀትና በአገልግሎት የተገነባ ጀመዓ', "Ye Birhan Dana is a community of EIADE students learning comparative religion and turning that knowledge into da'wah, education and care for the ummah.", 'የብርሃን ዳና የንጽጽር ሃይማኖትን የሚማሩ እና ያንን እውቀት ወደ ዳዕዋ፣ ትምህርት እና ለኡማው እንክብካቤ የሚቀይሩ የEIADE ተማሪዎች ማህበረሰብ ነው።', 3),
                'services' => $section('What we do', 'የምንሰራው', 'Programs & services', 'ፕሮግራሞችና አገልግሎቶች', 'From classrooms to stages to screens, we serve wherever the ummah needs us.', 'ከክፍል እስከ መድረክ እስከ ስክሪን — ኡማው በሚፈልገን ቦታ ሁሉ እናገለግላለን።', 7),
                'goals' => $section('Where we are heading', 'ወዴት እያመራን ነው', 'Our goals', 'ግቦቻችን', 'The milestones we are working towards, insha Allah.', 'ኢንሻአላህ ልናሳካቸው የምንሰራባቸው ግቦች።', 4),
                'journey' => $section('Your path', 'የእርስዎ መንገድ', 'Every journey starts with a step', 'እያንዳንዱ ጉዞ በአንድ እርምጃ ይጀምራል', null, null, 4),
                'articles' => $section('Read & reflect', 'ያንብቡ ያስተንትኑ', 'Latest articles', 'የቅርብ ጊዜ ጽሑፎች', 'Reflections, answers to common questions and news from the jemea.', 'ከጀመዓው የተገኙ ጽሑፎች፣ ለተለመዱ ጥያቄዎች መልሶች እና ዜናዎች።', 3),
                'events' => $section('Coming up', 'በቅርቡ', 'Upcoming events', 'የሚመጡ ዝግጅቶች', 'Lectures, trainings and gatherings you can join.', 'ሊሳተፉባቸው የሚችሉ ትምህርቶች፣ ስልጠናዎች እና ስብሰባዎች።', 3),
                'gallery' => $section('Moments', 'ትውስታዎች', 'Gallery', 'የፎቶ ማዕከል', null, null, 5),
                'testimonials' => $section('In their words', 'በራሳቸው አንደበት', 'What our students say', 'ተማሪዎቻችን ምን ይላሉ', null, null, 3),
                'team' => $section('The people', 'ሰዎቹ', 'Meet the team', 'ቡድኑን ይተዋወቁ', 'Volunteers who make every program possible.', 'እያንዳንዱን ፕሮግራም እውን የሚያደርጉ በጎ ፈቃደኞች።', 4, false),
            ],
            'show_cta' => true,
            'cta_title' => 'Walk the path of light with us',
            'cta_title_am' => 'በብርሃን መንገድ ከእኛ ጋር ይጓዙ',
            'cta_text' => 'Registration is open for the next batch. Join as a student, volunteer or supporter.',
            'cta_text_am' => 'ለቀጣዩ ባች ምዝገባ ተከፍቷል። እንደ ተማሪ፣ በጎ ፈቃደኛ ወይም ደጋፊ ይቀላቀሉ።',
            'cta_primary_label' => 'Register now',
            'cta_primary_label_am' => 'አሁን ይመዝገቡ',
            'cta_primary_url' => '/register',
            'cta_secondary_label' => 'Support / Donate',
            'cta_secondary_label_am' => 'ይደግፉ / ይለግሱ',
            'cta_secondary_url' => '/donate',
        ];
        foreach ($h as $k => $v) {
            $this->migrator->add("homepage.$k", $v);
        }

        // ---------------- Navigation ----------------
        $this->migrator->add('navigation.header_links', [
            ['label' => 'About', 'label_am' => 'ስለ እኛ', 'url' => '/about', 'children' => [
                ['label' => 'About us', 'label_am' => 'ስለ እኛ', 'url' => '/about'],
                ['label' => 'Our goals', 'label_am' => 'ግቦቻችን', 'url' => '/goals'],
                ['label' => 'Team', 'label_am' => 'ቡድን', 'url' => '/team'],
                ['label' => 'FAQ', 'label_am' => 'ተደጋጋሚ ጥያቄዎች', 'url' => '/faq'],
            ]],
            ['label' => 'Services', 'label_am' => 'አገልግሎቶች', 'url' => '/services', 'children' => []],
            ['label' => 'Programs', 'label_am' => 'ፕሮግራሞች', 'url' => '/programs', 'children' => []],
            ['label' => 'Articles', 'label_am' => 'ጽሑፎች', 'url' => '/articles', 'children' => []],
            ['label' => 'Events', 'label_am' => 'ዝግጅቶች', 'url' => '/events', 'children' => [
                ['label' => 'Events', 'label_am' => 'ዝግጅቶች', 'url' => '/events'],
                ['label' => 'Announcements', 'label_am' => 'ማስታወቂያዎች', 'url' => '/announcements'],
            ]],
            ['label' => 'Gallery', 'label_am' => 'ፎቶዎች', 'url' => '/gallery', 'children' => []],
            ['label' => 'Contact', 'label_am' => 'ያግኙን', 'url' => '/contact', 'children' => []],
        ]);
        $this->migrator->add('navigation.header_cta_label', 'Register');
        $this->migrator->add('navigation.header_cta_label_am', 'ይመዝገቡ');
        $this->migrator->add('navigation.header_cta_url', '/register');
        $this->migrator->add('navigation.footer_columns', [
            ['title' => 'Programs', 'title_am' => 'ፕሮግራሞች', 'links' => [
                ['label' => 'All services', 'label_am' => 'ሁሉም አገልግሎቶች', 'url' => '/services'],
                ['label' => 'Programs & batches', 'label_am' => 'ፕሮግራሞችና ባቾች', 'url' => '/programs'],
                ['label' => 'Events', 'label_am' => 'ዝግጅቶች', 'url' => '/events'],
                ['label' => 'Register', 'label_am' => 'ይመዝገቡ', 'url' => '/register'],
            ]],
            ['title' => 'Jemea', 'title_am' => 'ጀመዓ', 'links' => [
                ['label' => 'About', 'label_am' => 'ስለ እኛ', 'url' => '/about'],
                ['label' => 'Goals', 'label_am' => 'ግቦች', 'url' => '/goals'],
                ['label' => 'Articles', 'label_am' => 'ጽሑፎች', 'url' => '/articles'],
                ['label' => 'Gallery', 'label_am' => 'ፎቶዎች', 'url' => '/gallery'],
            ]],
            ['title' => 'Support', 'title_am' => 'ድጋፍ', 'links' => [
                ['label' => 'Donate', 'label_am' => 'ይለግሱ', 'url' => '/donate'],
                ['label' => 'Contact', 'label_am' => 'ያግኙን', 'url' => '/contact'],
                ['label' => 'FAQ', 'label_am' => 'ጥያቄዎች', 'url' => '/faq'],
            ]],
        ]);

        // ---------------- Payment ----------------
        $this->migrator->add('payment.accounts', [
            ['method' => 'cbe', 'bank_name' => 'Commercial Bank of Ethiopia', 'account_name' => 'Ye Birhan Dana', 'account_number' => '1000XXXXXXXXX', 'note' => null, 'note_am' => null, 'show_on_donate' => true],
            ['method' => 'telebirr', 'bank_name' => 'Telebirr', 'account_name' => 'Ye Birhan Dana', 'account_number' => '09XXXXXXXX', 'note' => null, 'note_am' => null, 'show_on_donate' => true],
        ]);
        $this->migrator->add('payment.payment_instructions', 'Pay to one of the accounts below, then send the transaction reference and a screenshot of the receipt through our Telegram bot using /pay.');
        $this->migrator->add('payment.payment_instructions_am', 'ከታች ካሉት ሂሳቦች ወደ አንዱ ይክፈሉ፣ ከዚያም የግብይት ቁጥሩን እና የደረሰኙን ፎቶ በቴሌግራም ቦታችን /pay በመጠቀም ይላኩ።');
        $this->migrator->add('payment.donate_intro', 'Your sadaqah funds trainings, charity drives and da\'wah materials. May Allah reward you.');
        $this->migrator->add('payment.donate_intro_am', 'ሰደቃዎ ስልጠናዎችን፣ የበጎ አድራጎት ዘመቻዎችን እና የዳዕዋ ቁሳቁሶችን ይደግፋል። አላህ ይመንዳዎ።');

        // ---------------- Bot ----------------
        $this->migrator->add('bot.enabled', true);
        $this->migrator->add('bot.bot_username', env('TELEGRAM_BOT_USERNAME', 'YeBirhanDanaBot'));
        $this->migrator->add('bot.finance_group_chat_id', env('TELEGRAM_FINANCE_GROUP_ID'));
        $this->migrator->add('bot.public_channel_id', env('TELEGRAM_CHANNEL_ID'));
        $this->migrator->add('bot.welcome_message', 'Assalamu alaikum! Welcome to Ye Birhan Dana. Here you can check your balance, pay dues and receive announcements.');
        $this->migrator->add('bot.welcome_message_am', 'አሰላሙ ዓለይኩም! ወደ የብርሃን ዳና እንኳን በደህና መጡ። እዚህ ቀሪ ሂሳብዎን ማየት፣ ክፍያ መፈጸም እና ማስታወቂያዎችን መቀበል ይችላሉ።');
        $this->migrator->add('bot.weekly_summary_enabled', true);
        $this->migrator->add('bot.weekly_summary_day', 'saturday');
        $this->migrator->add('bot.weekly_summary_time', '09:00');

        // ---------------- Reminders ----------------
        $this->migrator->add('reminders.enabled', true);
        $this->migrator->add('reminders.days_before', 3);
        $this->migrator->add('reminders.overdue_interval_days', 7);
        $this->migrator->add('reminders.overdue_max_reminders', 3);
        $this->migrator->add('reminders.quiet_start', '22:00');
        $this->migrator->add('reminders.quiet_end', '07:00');
        $this->migrator->add('reminders.jumuah_pause', true);
        $this->migrator->add('reminders.jumuah_start', '12:00');
        $this->migrator->add('reminders.jumuah_end', '13:30');

        // ---------------- Registration ----------------
        $this->migrator->add('registration.open', true);
        $this->migrator->add('registration.closed_message', 'Registration is currently closed. Follow us on Telegram to hear when the next batch opens.');
        $this->migrator->add('registration.closed_message_am', 'ምዝገባ በአሁኑ ጊዜ ተዘግቷል። ቀጣዩ ባች ሲከፈት ለማወቅ በቴሌግራም ይከተሉን።');
        $this->migrator->add('registration.consent_text', 'As the parent/guardian, I consent to this student taking part in the program.');
        $this->migrator->add('registration.consent_text_am', 'እንደ ወላጅ/አሳዳጊ፣ ይህ ተማሪ በፕሮግራሙ እንዲሳተፍ እፈቅዳለሁ።');
        $this->migrator->add('registration.photo_consent_text', 'I agree that photos taken during the program may be published on the jemea\'s website and channels.');
        $this->migrator->add('registration.photo_consent_text_am', 'በፕሮግራሙ ወቅት የሚነሱ ፎቶዎች በጀመዓው ድረ-ገጽ እና ቻናሎች ላይ እንዲወጡ እስማማለሁ።');
        $this->migrator->add('registration.thank_you_title', 'Jazakallahu khairan!');
        $this->migrator->add('registration.thank_you_title_am', 'ጀዛከላሁ ኸይረን!');
        $this->migrator->add('registration.thank_you_text', 'Your registration has been received. Our team will review it and confirm your place. Connect on Telegram to get updates instantly.');
        $this->migrator->add('registration.thank_you_text_am', 'ምዝገባዎ ደርሶናል። ቡድናችን ገምግሞ ቦታዎን ያረጋግጣል። ፈጣን መረጃ ለማግኘት በቴሌግራም ይገናኙ።');
        $this->migrator->add('registration.turnstile_enabled', false);
        $this->migrator->add('registration.turnstile_site_key', null);
    }
};
