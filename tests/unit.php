<?php
// Unit test fungsi murni (tanpa database): php tests/unit.php
require __DIR__ . '/../env.php';
require __DIR__ . '/../content.php';
require __DIR__ . '/../journal.php';

$pass = 0;
$fail = 0;
function check($name, $actual, $expected)
{
    global $pass, $fail;
    if ($actual === $expected) {
        $pass++;
        echo "PASS  $name\n";
    } else {
        $fail++;
        echo "FAIL  $name -> got " . var_export($actual, true) . " want " . var_export($expected, true) . "\n";
    }
}

echo "== writing_streaks ==\n";
check('no entries', writing_streaks([], '2026-11-16'), ['current' => 0, 'longest' => 0, 'last' => null]);
check('wrote today only', writing_streaks(['2026-11-16'], '2026-11-16'), ['current' => 1, 'longest' => 1, 'last' => '2026-11-16']);
check('3 days ending today', writing_streaks(['2026-11-14', '2026-11-15', '2026-11-16'], '2026-11-16')['current'], 3);
check('streak still alive if wrote yesterday', writing_streaks(['2026-11-14', '2026-11-15'], '2026-11-16')['current'], 2);
check('streak broken after a missed day', writing_streaks(['2026-11-13', '2026-11-14'], '2026-11-16')['current'], 0);
check('duplicates & order ignored', writing_streaks(['2026-11-16', '2026-11-15', '2026-11-16', '2026-11-15'], '2026-11-16')['current'], 2);
check('longest across gaps', writing_streaks(['2026-10-01', '2026-10-02', '2026-10-03', '2026-10-04', '2026-11-15', '2026-11-16'], '2026-11-16'), ['current' => 2, 'longest' => 4, 'last' => '2026-11-16']);
check('future dates ignored', writing_streaks(['2026-11-16', '2026-11-17', '2026-11-18'], '2026-11-16'), ['current' => 1, 'longest' => 1, 'last' => '2026-11-16']);
check('crosses month & year', writing_streaks(['2025-12-30', '2025-12-31', '2026-01-01'], '2026-01-01')['current'], 3);

echo "== activity grid ==\n";
$grid = activity_grid(['2026-11-16' => 2, '2026-11-10' => 1], '2026-11-16', 26);
check('26 weeks', count($grid), 26);
check('7 days per week', count($grid[0]), 7);
check('weeks start on Monday', date('N', strtotime($grid[0][0]['date'])), '1');
check('last week contains today', $grid[25][0]['date'], '2026-11-16');
check('count placed on date', $grid[25][0]['count'], 2);
check('earlier count placed', $grid[24][1]['count'], 1);
check('days after today are future', $grid[25][1]['future'], true);
check('level 0', activity_level(0), 0);
check('level capped at 4', activity_level(9), 4);
check('count_by_day', count_by_day(['2026-11-16', '2026-11-16', '2026-11-15']), ['2026-11-16' => 2, '2026-11-15' => 1]);

echo "== mood ==\n";
check('valid mood', valid_mood('senang'), 'senang');
check('invalid mood', valid_mood('<script>'), null);
check('mood label', mood_label('lelah'), '😴 Lelah');
check('no mood label', mood_label(null), '');

echo "== slug, tags, excerpt ==\n";
check('slugify basic', slugify('Belajar PHP & AI!'), 'belajar-php-ai');
check('slugify path traversal', slugify('../../Etc <script>'), 'etc-script');
check('slugify empty fallback', slugify('!!!'), 'artikel');
check('parse_tags dedupe + trim', parse_tags(' PHP, php ,  Tips  Belajar,,'), ['php' => 'PHP', 'tips-belajar' => 'Tips Belajar']);
check('parse_tags max 8', count(parse_tags('a1,a2,a3,a4,a5,a6,a7,a8,a9,a10')), 8);
check('parse_tags skips symbols only', parse_tags('###, ok'), ['ok' => 'ok']);
check('excerpt strips html', excerpt('<p>Halo <b>dunia</b></p>'), 'Halo dunia');
check('excerpt cuts on word', excerpt('satu dua tiga empat lima', 14), 'satu dua tiga…');

echo "\nRESULT: $pass passed, $fail failed\n";
exit($fail === 0 ? 0 : 1);
