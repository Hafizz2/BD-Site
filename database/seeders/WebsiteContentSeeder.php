<?php

namespace Database\Seeders;

use App\Models\ArticleCategory;
use App\Models\Faq;
use App\Models\Goal;
use App\Models\JourneyStep;
use App\Models\Page;
use App\Models\Service;
use App\Models\Stat;
use Illuminate\Database\Seeder;

/**
 * Starter website content (taken from the original home page design).
 * Everything here is editable in Admin → Website. Safe to re-run: uses updateOrCreate.
 */
class WebsiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->pages();
        $this->services();
        $this->journey();
        $this->stats();
        $this->goals();
        $this->faqs();
        $this->categories();
    }

    private function pages(): void
    {
        $pages = [
            'about' => ['About us', 'ስለ እኛ', 'Who we are', 'እኛ ማን ነን', "Ye Birhan Dana is a community of EIADE students learning comparative religion and turning that knowledge into da'wah, education and care for the ummah.", 'የብርሃን ዳና የንጽጽር ሃይማኖትን የሚማሩ እና ያንን እውቀት ወደ ዳዕዋ፣ ትምህርት እና ለኡማው እንክብካቤ የሚቀይሩ የEIADE ተማሪዎች ማህበረሰብ ነው።',
                '<h2>Our story</h2><p>Ye Birhan Dana (የብርሃን ዳና — "the trail of light") is a jemea formed by students of EIADE. We study comparative religion together and share what we learn through trainings, madrasa programs, charity, public da\'wah and digital content.</p><p>Edit this text in Admin → Website → Pages → About.</p>',
                '<h2>ታሪካችን</h2><p>የብርሃን ዳና በEIADE ተማሪዎች የተመሰረተ ጀመዓ ነው። የንጽጽር ሃይማኖትን አብረን እንማራለን፣ የተማርነውንም በስልጠናዎች፣ በመድረሳ ፕሮግራሞች፣ በበጎ አድራጎት፣ በሕዝባዊ ዳዕዋ እና በዲጂታል ይዘት እናካፍላለን።</p>'],
            'goals' => ['Our goals', 'ግቦቻችን', 'Where we are heading', 'ወዴት እያመራን ነው', 'The milestones we are working towards, insha Allah.', 'ኢንሻአላህ ልናሳካቸው የምንሰራባቸው ግቦች።', null, null],
            'services' => ['Programs & services', 'ፕሮግራሞችና አገልግሎቶች', 'What we do', 'የምንሰራው', 'From classrooms to stages to screens, we serve wherever the ummah needs us.', 'ከክፍል እስከ መድረክ እስከ ስክሪን — ኡማው በሚፈልገን ቦታ ሁሉ እናገለግላለን።', null, null],
            'programs' => ['Programs & batches', 'ፕሮግራሞችና ባቾች', 'Learn with us', 'ከእኛ ጋር ይማሩ', 'Find a program and join the next open batch.', 'ፕሮግራም ይምረጡ እና ቀጣዩን ክፍት ባች ይቀላቀሉ።', null, null],
            'articles' => ['Articles', 'ጽሑፎች', 'Read & reflect', 'ያንብቡ ያስተንትኑ', 'Reflections, answers to common questions and news from the jemea.', 'ከጀመዓው የተገኙ ጽሑፎች፣ ለተለመዱ ጥያቄዎች መልሶች እና ዜናዎች።', null, null],
            'events' => ['Events', 'ዝግጅቶች', 'Join us', 'ይቀላቀሉን', 'Lectures, trainings, stage programs and gatherings.', 'ትምህርቶች፣ ስልጠናዎች፣ የመድረክ ፕሮግራሞች እና ስብሰባዎች።', null, null],
            'gallery' => ['Gallery', 'የፎቶ ማዕከል', 'Moments', 'ትውስታዎች', 'Moments from our trainings, events, trips and charity drives.', 'ከስልጠናዎቻችን፣ ዝግጅቶቻችን፣ ጉዞዎቻችን እና የበጎ አድራጎት ሥራዎቻችን።', null, null],
            'team' => ['Our team', 'ቡድናችን', 'The people', 'ሰዎቹ', 'Volunteers who make every program possible.', 'እያንዳንዱን ፕሮግራም እውን የሚያደርጉ በጎ ፈቃደኞች።', null, null],
            'announcements' => ['Announcements', 'ማስታወቂያዎች', 'News', 'ዜና', 'The latest updates from the jemea.', 'ከጀመዓው የቅርብ ጊዜ መረጃዎች።', null, null],
            'faq' => ['Frequently asked questions', 'ተደጋጋሚ ጥያቄዎች', 'Help', 'እገዛ', 'Answers to the questions we hear most.', 'በብዛት ለሚነሱ ጥያቄዎች መልሶች።', null, null],
            'contact' => ['Contact us', 'ያግኙን', 'Get in touch', 'ይገናኙን', "We'd love to hear from you — send us a message or reach us on Telegram.", 'መልእክት ይላኩልን ወይም በቴሌግራም ያግኙን።', null, null],
            'donate' => ['Support our work', 'ሥራችንን ይደግፉ', 'Sadaqah', 'ሰደቃ', 'Your support funds trainings, charity and da\'wah.', 'ድጋፍዎ ስልጠናዎችን፣ በጎ አድራጎትን እና ዳዕዋን ያስቀጥላል።', null, null],
            'register' => ['Register', 'ምዝገባ', 'Join a program', 'ፕሮግራም ይቀላቀሉ', 'Pick a program and an open batch, then fill the short form.', 'ፕሮግራም እና ክፍት ባች ይምረጡ፣ ከዚያም አጭሩን ቅጽ ይሙሉ።', null, null],
        ];

        foreach ($pages as $key => [$t, $tAm, $e, $eAm, $i, $iAm, $b, $bAm]) {
            Page::updateOrCreate(['key' => $key], [
                'slug' => $key, 'title' => $t, 'title_am' => $tAm, 'eyebrow' => $e, 'eyebrow_am' => $eAm,
                'intro' => $i, 'intro_am' => $iAm, 'body' => $b, 'body_am' => $bAm, 'is_published' => true,
            ]);
        }
    }

    private function services(): void
    {
        $rows = [
            ['college-trainings', 'graduation', true, 'Summer & Winter College Student Trainings', 'የክረምትና የበጋ የኮሌጅ ተማሪዎች ስልጠና',
                'Intensive seasonal trainings for university students on comparative religion, logic, atheism and answering doubts — built to prepare confident, well-grounded young Muslims.',
                'ለዩኒቨርሲቲ ተማሪዎች በንጽጽር ሃይማኖት፣ በሎጂክ፣ በኤቲዝም እና ጥርጣሬዎችን በመመለስ ላይ የሚሰጡ ወቅታዊ ጥልቅ ስልጠናዎች።', 'Seasonal · Intensive', 'ወቅታዊ · ጥልቅ'],
            ['madrasa-training', 'book', false, 'Madrasa Training', 'የመድረሳ ስልጠና',
                'Structured training for madrasa students and teachers, from aqeedah foundations to comparative knowledge.',
                'ከዓቂዳ መሠረቶች እስከ ንጽጽር እውቀት ለመድረሳ ተማሪዎችና መምህራን የተዋቀረ ስልጠና።', 'Kids · Youth', 'ልጆች · ወጣቶች'],
            ['charity', 'heart', false, 'Charity', 'በጎ አድራጎት',
                'Supporting families and students in need through sadaqah drives, Ramadan packages and community support.',
                'በሰደቃ ዘመቻዎች፣ በረመዳን ስጦታዎች እና በማህበረሰብ ድጋፍ የተቸገሩ ቤተሰቦችንና ተማሪዎችን መደገፍ።', 'Sadaqah · Support', 'ሰደቃ · ድጋፍ'],
            ['stage-dawah-events', 'mic', false, "Stage Da'wah & Events", 'የመድረክ ዳዕዋ እና ዝግጅቶች',
                'Public lectures, panel discussions and event programs that present Islam clearly and answer questions live.',
                'ኢስላምን በግልጽ የሚያቀርቡ እና ጥያቄዎችን በቀጥታ የሚመልሱ ሕዝባዊ ትምህርቶች፣ ውይይቶች እና ዝግጅቶች።', 'Lectures · Events', 'ትምህርቶች · ዝግጅቶች'],
            ['trips', 'mountain', false, 'Trips', 'ጉዞዎች',
                'Educational and bonding trips that build brotherhood, sisterhood and memories along the path.',
                'ወንድማማችነትን፣ እህትማማችነትን እና ትዝታን የሚገነቡ ትምህርታዊ ጉዞዎች።', 'Community', 'ማህበረሰብ'],
            ['digital-dawah', 'phone', false, "Digital Da'wah", 'ዲጂታል ዳዕዋ',
                'Short videos, Telegram content and online resources that carry the message beyond the classroom.',
                'መልእክቱን ከክፍል ባሻገር የሚያደርሱ አጫጭር ቪዲዮዎች፣ የቴሌግራም ይዘቶች እና የኦንላይን ግብዓቶች።', 'Online', 'ኦንላይን'],
            ['research', 'search', false, 'Research & More', 'ጥናትና ሌሎች',
                'Community research to understand doubts and needs, so every program we run is informed and relevant.',
                'እያንዳንዱ ፕሮግራማችን በመረጃ የተደገፈ እንዲሆን ጥርጣሬዎችንና ፍላጎቶችን ለመረዳት የሚደረግ የማህበረሰብ ጥናት።', 'Growing', 'እያደገ'],
        ];

        foreach ($rows as $i => [$slug, $icon, $featured, $t, $tAm, $s, $sAm, $tag, $tagAm]) {
            Service::updateOrCreate(['slug' => $slug], [
                'title' => $t, 'title_am' => $tAm, 'summary' => $s, 'summary_am' => $sAm,
                'body' => '<p>'.e($s).'</p>', 'body_am' => '<p>'.e($sAm).'</p>',
                'icon' => $icon, 'tag' => $tag, 'tag_am' => $tagAm, 'is_featured' => $featured, 'is_active' => true, 'sort' => $i,
            ]);
        }
    }

    private function journey(): void
    {
        $steps = [
            ['Register', 'ይመዝገቡ', 'Pick a program and fill the short registration form.', 'ፕሮግራም ይምረጡ እና አጭሩን የምዝገባ ቅጽ ይሙሉ።'],
            ['Get confirmed', 'ማረጋገጫ ያግኙ', 'Our team reviews and confirms your spot via Telegram.', 'ቡድናችን ገምግሞ ቦታዎን በቴሌግራም ያረጋግጣል።'],
            ['Learn with your batch', 'ከባችዎ ጋር ይማሩ', 'Attend sessions with your batch and grow with mentors.', 'ከባችዎ ጋር ክፍለ ጊዜዎችን ይከታተሉ፣ ከአማካሪዎች ጋር ያድጉ።'],
            ['Graduate & serve', 'ተመርቀው ያገልግሉ', "Receive your certificate and join the da'wah team.", 'ሰርተፊኬትዎን ይቀበሉ እና የዳዕዋ ቡድኑን ይቀላቀሉ።'],
        ];
        foreach ($steps as $i => [$t, $tAm, $d, $dAm]) {
            JourneyStep::updateOrCreate(['sort' => $i], ['title' => $t, 'title_am' => $tAm, 'description' => $d, 'description_am' => $dAm]);
        }
    }

    private function stats(): void
    {
        $stats = [
            ['Batches', 'ባቾች', 12, null, null],
            ['Students', 'ተማሪዎች', 800, '+', null],
            ['Volunteers', 'በጎ ፈቃደኞች', 40, '+', null],
        ];
        foreach ($stats as $i => [$l, $lAm, $v, $suffix, $auto]) {
            Stat::updateOrCreate(['sort' => $i], ['label' => $l, 'label_am' => $lAm, 'value' => $v, 'suffix' => $suffix, 'auto_source' => $auto, 'is_active' => true]);
        }
    }

    private function goals(): void
    {
        // Sample goals — replace with the jemea's real goals in Admin → Website → Goals.
        $goals = [
            ['graduation', 'Train confident young du\'at', 'በራስ የሚተማመኑ ወጣት ዱዓቶችን ማሰልጠን', 'Run seasonal trainings that equip university students to understand their deen and answer doubts with wisdom.', 'ዩኒቨርሲቲ ተማሪዎች ዲናቸውን እንዲረዱ እና ጥርጣሬዎችን በጥበብ እንዲመልሱ የሚያስችሉ ወቅታዊ ስልጠናዎችን ማካሄድ።'],
            ['book', 'Strengthen madrasa education', 'የመድረሳ ትምህርትን ማጠናከር', 'Support madrasa students and teachers with structured, comparative-knowledge curricula.', 'የመድረሳ ተማሪዎችንና መምህራንን በተዋቀረ የንጽጽር እውቀት ሥርዓተ ትምህርት መደገፍ።'],
            ['hands', 'Serve families in need', 'የተቸገሩ ቤተሰቦችን ማገልገል', 'Grow our charity drives and Ramadan support every year.', 'የበጎ አድራጎት ዘመቻዎቻችንን እና የረመዳን ድጋፋችንን በየዓመቱ ማሳደግ።'],
            ['globe', 'Reach more people online', 'ብዙ ሰዎችን በኦንላይን መድረስ', "Publish quality digital da'wah content in Amharic and English.", 'ጥራት ያለው ዲጂታል የዳዕዋ ይዘት በአማርኛና በእንግሊዝኛ ማዘጋጀት።'],
        ];
        foreach ($goals as $i => [$icon, $t, $tAm, $d, $dAm]) {
            Goal::updateOrCreate(['sort' => $i], ['icon' => $icon, 'title' => $t, 'title_am' => $tAm, 'description' => $d, 'description_am' => $dAm, 'is_active' => true]);
        }
    }

    private function faqs(): void
    {
        $faqs = [
            ['Who can join the trainings?', 'ስልጠናዎቹን ማን መቀላቀል ይችላል?', 'Each program lists who it is for. College trainings are for university students; madrasa programs are for children and youth. Check the program page for details.', 'እያንዳንዱ ፕሮግራም ለማን እንደሆነ ይገልጻል። የኮሌጅ ስልጠናዎች ለዩኒቨርሲቲ ተማሪዎች፣ የመድረሳ ፕሮግራሞች ለልጆችና ወጣቶች ናቸው።'],
            ['How do I know my registration was accepted?', 'ምዝገባዬ መቀበሉን እንዴት አውቃለሁ?', 'After you register, connect to our Telegram bot from the thank-you page. You will get a message as soon as our team reviews your registration.', 'ከተመዘገቡ በኋላ ከምስጋና ገጹ ወደ ቴሌግራም ቦታችን ይገናኙ። ቡድናችን ምዝገባዎን ሲገመግም መልእክት ይደርስዎታል።'],
            ['How do I pay fees?', 'ክፍያ እንዴት እፈጽማለሁ?', 'Pay to one of the accounts on the Donate page, then send the receipt through the Telegram bot with /pay.', 'በልገሳ ገጹ ላይ ካሉት ሂሳቦች ወደ አንዱ ይክፈሉ፣ ከዚያም ደረሰኙን በቴሌግራም ቦቱ /pay በመጠቀም ይላኩ።'],
        ];
        foreach ($faqs as $i => [$q, $qAm, $a, $aAm]) {
            Faq::updateOrCreate(['sort' => $i], ['question' => $q, 'question_am' => $qAm, 'answer' => $a, 'answer_am' => $aAm, 'is_published' => true]);
        }
    }

    private function categories(): void
    {
        foreach ([['Reflections', 'ማስተንተኛ'], ['Comparative religion', 'ንጽጽር ሃይማኖት'], ['News', 'ዜና']] as $i => [$n, $nAm]) {
            ArticleCategory::updateOrCreate(['slug' => \Illuminate\Support\Str::slug($n)], ['name' => $n, 'name_am' => $nAm, 'sort' => $i]);
        }
    }
}
