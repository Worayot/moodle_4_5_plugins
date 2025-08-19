<?php
require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('local/analytics:view', context_system::instance());

global $DB;

// Include libraries
require_once($CFG->libdir . '/tcpdf/tcpdf.php');       // PDF
require_once($CFG->dirroot . '/lib/phpspreadsheet/vendor/autoload.php'); // Excel
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Params
$format = optional_param('format', 'pdf', PARAM_ALPHA);
$teamid = optional_param('teamid', 0, PARAM_INT); // Optional: filter by team
$search = optional_param('search', '', PARAM_TEXT);
$filterstatus = optional_param('status', '', PARAM_ALPHA);

// 1. Fetch team members (or all users if no teamid)
if ($teamid) {
    $users = $DB->get_records('user', ['profile_field_team' => $teamid, 'deleted' => 0, 'suspended' => 0]);
} else {
    $users = $DB->get_records('user', ['deleted' => 0, 'suspended' => 0]);
}

// Apply search filter
if ($search) {
    $users = array_filter($users, fn($u) => stripos($u->firstname . ' ' . $u->lastname, $search) !== false);
}

// 2. Collect data per user
$data = [];
$team_completion_sum = 0;
$team_quiz_sum = 0;
$active_count = 0;

foreach ($users as $user) {
    $courses = enrol_get_all_users_courses($user->id, true);
    $completed = 0;
    $inprogress = 0;
    $lastaccess_times = [];

    foreach ($courses as $course) {
        $info = new completion_info($course);
        $iscompleted = ($info->is_enabled() && $info->is_course_complete($user->id));
        if ($iscompleted) $completed++;
        else $inprogress++;

        $lastaccess_times[] = $course->timemodified ?? 0;

        // Apply course status filter
        if ($filterstatus && 
            ($filterstatus === 'Completed' && !$iscompleted) || 
            ($filterstatus === 'In Progress' && $iscompleted)) {
            continue 2; // skip this user
        }
    }

    // Average quiz grade for this user
    $avggrade = $DB->get_field_sql("
        SELECT AVG(gg.finalgrade / gi.grademax * 100)
          FROM {grade_grades} gg
          JOIN {grade_items} gi ON gi.id = gg.itemid
         WHERE gi.itemtype = 'mod'
           AND gi.itemmodule = 'quiz'
           AND gg.userid = :userid
    ", ['userid' => $user->id]);

    $lastaccess = $lastaccess_times ? max($lastaccess_times) : 0;

    $data[] = [
        'fullname' => fullname($user),
        'courses_total' => count($courses),
        'courses_completed' => $completed,
        'courses_inprogress' => $inprogress,
        'average_quiz' => $avggrade ? round($avggrade,2) : 0,
        'lastaccess' => $lastaccess ? userdate($lastaccess) : 'Never',
        'status' => $lastaccess ? 'Active' : 'Inactive',
    ];

    $team_completion_sum += $completed / max(count($courses),1);
    $team_quiz_sum += $avggrade ? round($avggrade,2) : 0;
    if ($lastaccess) $active_count++;
}

// 3. Team summary
$team_summary = [
    'team_members' => count($users),
    'avg_completion' => $team_completion_sum ? round($team_completion_sum / count($users) * 100, 2) : 0,
    'avg_quiz' => $team_quiz_sum ? round($team_quiz_sum / count($users), 2) : 0,
    'active_users' => $active_count,
    'inactive_users' => count($users) - $active_count,
];

// 4. Export
if ($format === 'excel') {
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(['Name','Total Courses','Completed','In Progress','Avg Quiz','Last Access','Status'], NULL, 'A1');
    $row = 2;
    foreach ($data as $d) {
        $sheet->fromArray(array_values($d), NULL, "A$row");
        $row++;
    }
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="team_report.xlsx"');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} else {
    $pdf = new TCPDF();
    $pdf->AddPage();
    $html = '<h1>Team Report Dashboard</h1>';
    $html .= "<h2>Team Summary</h2>
        <ul>
            <li>Team Members: {$team_summary['team_members']}</li>
            <li>Average Completion: {$team_summary['avg_completion']}%</li>
            <li>Average Quiz Score: {$team_summary['avg_quiz']}%</li>
            <li>Active / Inactive: {$team_summary['active_users']} / {$team_summary['inactive_users']}</li>
        </ul>";
    $html .= "<h2>Individual Reports</h2>
        <table border='1' cellpadding='4'>
            <tr>
                <th>Name</th><th>Total Courses</th><th>Completed</th><th>In Progress</th><th>Avg Quiz</th><th>Last Access</th><th>Status</th>
            </tr>";
    foreach ($data as $d) {
        $html .= "<tr>
                    <td>{$d['fullname']}</td>
                    <td>{$d['courses_total']}</td>
                    <td>{$d['courses_completed']}</td>
                    <td>{$d['courses_inprogress']}</td>
                    <td>{$d['average_quiz']}</td>
                    <td>{$d['lastaccess']}</td>
                    <td>{$d['status']}</td>
                  </tr>";
    }
    $html .= '</table>';
    $pdf->writeHTML($html);
    $pdf->Output('team_report.pdf', 'D');
    exit;
}
