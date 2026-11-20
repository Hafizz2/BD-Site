<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Enums\BatchSeason;
use App\Enums\BatchStatus;
use App\Enums\ProgramType;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Batch;
use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\Program;
use App\Models\Service;
use App\Support\RegistrationForm;
use Illuminate\Database\Seeder;

/**
 * Sample website content — seeded EVERYWHERE, including the live server, so the site isn't empty:
 * programs, batches (+ a sample registration form), articles, events, gallery albums.
 * No people at all — members, students and registrations are confidential.
 *
 * No students, registrations or money here (those are DemoSeeder, local only).
 * Safe to run again: it only adds what's missing and never overwrites edits made in the admin panel.
 */
class SampleContentSeeder extends Seeder
{
    public function run(): void
    {
        // On the live server sample batches start closed, so nobody registers for a sample batch by accident.
        $open = app()->environment('local');

        // ---------- Programs & batches ----------
        $programs = collect([
            ['college-trainings', 'Summer & Winter College Trainings', 'የክረምትና የበጋ የኮሌጅ ስልጠና', ProgramType::CollegeTraining, 'graduation'],
            ['madrasa', 'Madrasa Training', 'የመድረሳ ስልጠና', ProgramType::Madrasa, 'book'],
            ['kids', 'Kids Weekend Program', 'የልጆች የሳምንት መጨረሻ ፕሮግራም', ProgramType::KidsTraining, 'star'],
            ['charity', 'Ramadan Charity Drive', 'የረመዳን የበጎ አድራጎት ዘመቻ', ProgramType::Charity, 'heart'],
        ])->map(fn ($p, $i) => Program::firstOrCreate(['slug' => $p[0]], [
            'name' => $p[1], 'name_am' => $p[2], 'type' => $p[3], 'icon' => $p[4], 'sort' => $i,
            'summary' => 'A structured program run by Ye Birhan Dana volunteers. Edit this description in the admin panel.',
            'summary_am' => 'በየብርሃን ዳና በጎ ፈቃደኞች የሚካሄድ የተዋቀረ ፕሮግራም።',
            'description' => '<p>Sessions cover foundations of belief, comparative religion and practical da\'wah skills, with mentors from the jemea.</p>',
            'is_active' => true, 'show_on_website' => true,
        ]));
        [$college, $madrasa, $kids] = [$programs[0], $programs[1], $programs[2]];

        Batch::firstOrCreate(['program_id' => $college->id, 'name' => 'Batch 4'], ['season' => BatchSeason::Summer, 'start_date' => now()->subMonths(5)->startOfMonth(), 'end_date' => now()->subMonths(3), 'capacity' => 60, 'registration_open' => false, 'status' => BatchStatus::Completed]);
        Batch::firstOrCreate(['program_id' => $college->id, 'name' => 'Batch 5'], ['season' => BatchSeason::Winter, 'start_date' => now()->subWeeks(2), 'end_date' => now()->addWeeks(6), 'capacity' => 60, 'registration_open' => false, 'status' => BatchStatus::Active, 'location' => 'Anwar Mosque hall', 'schedule_note' => 'Saturdays & Sundays, 2:00–5:00 PM', 'schedule_note_am' => 'ቅዳሜና እሁድ፣ 8:00–11:00 ሰዓት']);
        $b6 = Batch::firstOrCreate(['program_id' => $college->id, 'name' => 'Batch 6'], ['season' => BatchSeason::Summer, 'start_date' => now()->addMonths(2)->startOfMonth(), 'end_date' => now()->addMonths(4), 'capacity' => 80, 'registration_open' => $open, 'status' => BatchStatus::Planned, 'location' => 'Anwar Mosque hall']);
        Batch::firstOrCreate(['program_id' => $madrasa->id, 'name' => 'Madrasa Batch 2'], ['season' => BatchSeason::Regular, 'start_date' => now()->subMonth(), 'end_date' => now()->addMonths(5), 'capacity' => 40, 'registration_open' => $open, 'status' => BatchStatus::Active]);
        Batch::firstOrCreate(['program_id' => $kids->id, 'name' => 'Kids Term 1'], ['season' => BatchSeason::Regular, 'start_date' => now()->addWeeks(3), 'end_date' => now()->addMonths(3), 'capacity' => 30, 'registration_open' => $open, 'status' => BatchStatus::Planned]);

        // A sample Google-Forms-style registration form on Batch 6 (only if nobody has set one yet).
        if (blank($b6->form_fields)) {
            $fields = RegistrationForm::defaults();
            array_splice($fields, 4, 0, [['key' => 'q_studies', 'type' => 'section', 'builtin' => false, 'label' => 'About your studies', 'label_am' => 'ስለ ትምህርትዎ',
                'help' => 'Tell us a little about where you study.', 'required' => false, 'enabled' => true, 'options' => []]]);
            $fields[] = ['key' => 'q_day', 'type' => 'select', 'builtin' => false, 'label' => 'Which day suits you best?', 'label_am' => 'የትኛው ቀን ይመችዎታል?',
                'help' => null, 'required' => true, 'enabled' => true, 'options' => ['Saturday', 'Sunday']];
            $fields[] = ['key' => 'q_before', 'type' => 'yes_no', 'builtin' => false, 'label' => 'Have you studied comparative religion before?', 'label_am' => 'ከዚህ በፊት ንጽጽር ሃይማኖት ተምረዋል?',
                'help' => null, 'required' => false, 'enabled' => true, 'options' => []];
            $b6->forceFill([
                'form_fields' => $fields,
                'form_settings' => array_merge($b6->form_settings ?? [], [
                    'title' => 'Summer College Training — Batch 6',
                    'title_am' => 'የክረምት የኮሌጅ ስልጠና — ባች 6',
                    'description' => "Assalamu alaikum! Fill in this short form to join our summer comparative-religion training.\nAfter you register we will call you for a short interview.",
                    'description_am' => "አሰላሙ ዐለይኩም! በክረምቱ የንጽጽር ሃይማኖት ስልጠና ለመሳተፍ ይህን አጭር ቅጽ ይሙሉ።\nከተመዘገቡ በኋላ ለአጭር ቃለ መጠይቅ እንደውልልዎታለን።",
                    'thanks' => 'Jazakallahu khairan! We will call you within a week.',
                    'thanks_am' => 'ጀዛከላሁ ኸይረን! በአንድ ሳምንት ውስጥ እንደውልልዎታለን።',
                ]),
            ])->save();
        }

        Service::where('slug', 'college-trainings')->whereNull('program_id')->update(['program_id' => $college->id]);
        Service::where('slug', 'madrasa-training')->whereNull('program_id')->update(['program_id' => $madrasa->id]);

        // ---------- Articles ----------
        $cats = ArticleCategory::all();
        $titles = [
            ['Why comparative religion matters for young Muslims', 'ንጽጽር ሃይማኖት ለወጣት ሙስሊሞች ለምን ያስፈልጋል'],
            ['Highlights from the winter training', 'ከበጋው ስልጠና የተወሰዱ ዋና ዋና ነጥቦች'],
            ['Answering common questions with wisdom', 'የተለመዱ ጥያቄዎችን በጥበብ መመለስ'],
            ['Our Ramadan charity drive: a thank-you', 'የረመዳን የበጎ አድራጎት ዘመቻችን፡ ምስጋና'],
            ['How to prepare for a stage da\'wah program', 'ለመድረክ ዳዕዋ ፕሮግራም እንዴት መዘጋጀት ይቻላል'],
        ];
        foreach ($titles as $i => [$t, $tAm]) {
            Article::firstOrCreate(['title' => $t], [
                'title_am' => $tAm, 'article_category_id' => $cats[$i % max(1, $cats->count())]?->id,
                'author_name' => 'Ye Birhan Dana', 'excerpt' => 'A short reflection from the jemea. Replace this sample article with real content in the admin panel.',
                'excerpt_am' => 'ከጀመዓው የተገኘ አጭር ማስተንተኛ።',
                'body' => '<p>'.str_repeat('Knowledge is a light, and sharing it with wisdom is a trust. ', 20).'</p><h2>What we learned</h2><p>'.str_repeat('Every batch brings new questions and new strength. ', 15).'</p><blockquote>Invite to the way of your Lord with wisdom and good instruction.</blockquote>',
                'body_am' => '<p>'.str_repeat('እውቀት ብርሃን ነው፤ በጥበብ ማካፈሉም አደራ ነው። ', 20).'</p>',
                'is_featured' => $i === 0, 'status' => ArticleStatus::Published, 'published_at' => now()->subDays($i * 6 + 1),
            ]);
        }

        // ---------- Events ----------
        foreach ([[14, 'Open lecture: Faith & reason', 'ክፍት ትምህርት፡ እምነትና አመክንዮ'], [30, 'Batch 6 orientation day', 'የባች 6 የመግቢያ ቀን'], [-20, 'Winter training graduation', 'የበጋ ስልጠና ምረቃ']] as [$d, $t, $tAm]) {
            Event::firstOrCreate(['title' => $t], ['title_am' => $tAm, 'starts_at' => now()->addDays($d)->setTime(14, 0), 'ends_at' => now()->addDays($d)->setTime(17, 0), 'location' => 'Addis Ababa', 'location_am' => 'አዲስ አበባ', 'summary' => 'Join us for an afternoon of learning and community.', 'summary_am' => 'ለአንድ ከሰዓት ትምህርትና ማህበረሰብ ይቀላቀሉን።', 'program_id' => $college->id, 'is_published' => true]);
        }

        // ---------- Gallery albums (add photos in the admin panel) ----------
        foreach ([['Winter Training 2026', false], ['Stage Da\'wah', false], ['Madrasa Program', true], ['Charity Drive', false], ['Annual Trip', false]] as $i => [$t, $minorsIn]) {
            GalleryAlbum::firstOrCreate(['title' => $t], ['taken_on' => now()->subMonths($i + 1), 'contains_minors' => $minorsIn, 'consent_confirmed' => false, 'is_published' => true, 'sort' => $i]);
        }

    }
}
