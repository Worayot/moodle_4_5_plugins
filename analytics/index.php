<?php
// local/analytics/index.php
require_once(__DIR__ . '/../../config.php');

require_login(); // must be logged in
$context = context_system::instance();
require_once($CFG->libdir . '/completionlib.php');

// Check if user has full access (manager/teacher/admin)
$userhasfullaccess = has_capability('moodle/course:manageactivities', $context) || is_siteadmin();

// Require capability only for full-access users
if ($userhasfullaccess) {
    require_capability('local/analytics:view', $context);
}

// Page setup
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_analytics'));
$PAGE->set_heading(get_string('pluginname', 'local_analytics'));
$PAGE->set_pagelayout('report');
$PAGE->requires->css(new moodle_url('/local/analytics/styles.css'));

// Determine requested nav
$nav = optional_param('nav', 'overview', PARAM_ALPHANUMEXT);

// Full list of nav items
$allnavitems = [
    'overview'            => ['icon' => '📊', 'label' => get_string('individualreport','local_analytics')],
    'reports'             => ['icon' => '📈', 'label' => get_string('teamreport','local_analytics')],
    'organization_report' => ['icon' => '🏢', 'label' => get_string('organizationreport','local_analytics')],
    'advanced_analytics'  => ['icon' => '🧠', 'label' => get_string('advancedanalytics','local_analytics')],
    'export_engine'       => ['icon' => '💾', 'label' => get_string('exportengine','local_analytics')],
    'course_insights'     => ['icon' => '💡', 'label' => get_string('courseinsights','local_analytics')],
];

// Determine allowed nav keys
$allowednavkeys = $userhasfullaccess ? array_keys($allnavitems) : ['overview'];

// Force nav to allowed keys
if (!in_array($nav, $allowednavkeys)) {
    $nav = 'overview';
}

// Build nav items for rendering
$navitems = [];
foreach ($allowednavkeys as $key) {
    $navitems[] = [
        'key'    => $key,
        'icon'   => $allnavitems[$key]['icon'],
        'label'  => $allnavitems[$key]['label'],
        'url'    => new moodle_url('/local/analytics/index.php', ['nav'=>$key]),
        'active' => ($nav === $key),
    ];
}

global $USER, $DB;

// =========================================================================
// ✅ Step 1: Prepare all individual user data first.
// This is a single source of truth for both the page and the downloads.
// =========================================================================
$enrolledcourses = enrol_get_all_users_courses($USER->id, true);
$courses_inprogress = 0;
$courses_completed = 0;

foreach ($enrolledcourses as $course) {
    $info = new completion_info($course);
    if ($info->is_enabled() && $info->is_course_complete($USER->id)) {
        $courses_completed++;
    } else {
        $courses_inprogress++;
    }
}

$quizgrades = $DB->get_records_sql("
    SELECT gg.finalgrade / gi.grademax * 100 AS percent,
           gg.timemodified, gi.itemname
      FROM {grade_grades} gg
      JOIN {grade_items} gi ON gi.id = gg.itemid
     WHERE gi.itemtype = 'mod'
       AND gi.itemmodule = 'quiz'
       AND gg.userid = :userid
  ORDER BY gg.timemodified DESC
     LIMIT 10
", ['userid' => $USER->id]);

$courses_saved = $DB->count_records_select(
    'favourite',
    'userid = :uid AND component = :comp AND (itemtype = :it1 OR itemtype = :it2)',
    ['uid' => $USER->id, 'comp' => 'core_course', 'it1' => 'course', 'it2' => 'courses']
);

$usercontext = [
    'fullname'           => fullname($USER),
    'position'           => $USER->profile_field_position ?? '',
    'department'         => $USER->profile_field_department ?? '',
    'team'               => $USER->profile_field_team ?? '',
    'firstaccess'        => $USER->firstaccess ? userdate($USER->firstaccess) : get_string('never'),
    'courses_enrolled'   => count($enrolledcourses),
    'courses_inprogress' => $courses_inprogress,
    'courses_completed'  => $courses_completed,
    'courses_saved'      => $courses_saved,
    'average_quiz'       => round($quizgrades ? array_sum(array_map(fn($q)=>$q->percent, $quizgrades))/count($quizgrades) : 0, 2),
    'lastaccess'         => $USER->lastaccess ? userdate($USER->lastaccess) : get_string('never'),
    'login_count'        => $DB->count_records('logstore_standard_log', ['userid' => $USER->id, 'action' => 'loggedin']),
];

// Prepare download URLs
$usercontext['download_pdf_url'] = (new moodle_url('/local/analytics/index.php', [
    'nav' => 'overview',
    'download' => 'pdf'
]))->out(false);
$usercontext['download_excel_url'] = (new moodle_url('/local/analytics/index.php', [
    'nav' => 'overview',
    'download' => 'excel'
]))->out(false);

// Prepare chart data
$usercontext['chart_completion'] = [
    'completed'  => $courses_completed,
    'inprogress' => $courses_inprogress,
    'enrolled'   => count($enrolledcourses),
];

// Format quiz history for Mustache
$usercontext['quiz_history'] = [];
foreach ($quizgrades as $q) {
    $usercontext['quiz_history'][] = [
        'time'  => userdate($q->timemodified, '%d/%m/%Y'),
        'score' => round($q->percent, 2),
        'itemname' => $q->itemname ?? ''
    ];
}

// =========================================================================
// ✅ Step 2: Handle Download requests. This must be done *before* rendering.
// =========================================================================
if ($nav === 'overview' && $downloadformat) {
    // Check for the specific download format
    if ($downloadformat === 'excel') {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Populate the sheet with the data we already fetched
        $sheet->setCellValue('A1', 'Fullname');
        $sheet->setCellValue('B1', $usercontext['fullname']);
        $sheet->setCellValue('A2', 'Department');
        $sheet->setCellValue('B2', $usercontext['department']);
        $sheet->setCellValue('A3', 'Team');
        $sheet->setCellValue('B3', $usercontext['team']);
        // ... add all other fields as needed
        $sheet->setCellValue('A4', 'Courses Enrolled');
        $sheet->setCellValue('B4', $usercontext['courses_enrolled']);
        $sheet->setCellValue('A5', 'Courses In Progress');
        $sheet->setCellValue('B5', $usercontext['courses_inprogress']);
        $sheet->setCellValue('A6', 'Courses Completed');
        $sheet->setCellValue('B6', $usercontext['courses_completed']);
        $sheet->setCellValue('A7', 'Starred Courses');
        $sheet->setCellValue('B7', $usercontext['courses_saved']);
        $sheet->setCellValue('A8', 'Average Quiz Score');
        $sheet->setCellValue('B8', $usercontext['average_quiz']);

        // Quiz history table
        $sheet->setCellValue('A10', 'Quiz');
        $sheet->setCellValue('B10', 'Score (%)');
        $sheet->setCellValue('C10', 'Date');
        $row = 11;
        foreach ($usercontext['quiz_history'] as $q) {
            $sheet->setCellValue("A$row", $q['itemname'] ?? '');
            $sheet->setCellValue("B$row", $q['score']);
            $sheet->setCellValue("C$row", $q['time']);
            $row++;
        }
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="individual_report.xlsx"');
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    } else if ($downloadformat === 'pdf') {
        $pdf = new TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);

        // Populate PDF with data we already fetched
        $html = "<h2>Individual Report for {$usercontext['fullname']}</h2>";
        $html .= "<p>Department: {$usercontext['department']}<br>";
        $html .= "Team: {$usercontext['team']}<br>";
        $html .= "Courses Enrolled: {$usercontext['courses_enrolled']}<br>";
        $html .= "Courses In Progress: {$usercontext['courses_inprogress']}<br>";
        $html .= "Courses Completed: {$usercontext['courses_completed']}<br>";
        $html .= "Starred Courses: {$usercontext['courses_saved']}<br>";
        $html .= "Average Quiz Score: {$usercontext['average_quiz']}%</p>";

        if ($usercontext['quiz_history']) {
            $html .= "<h3>Quiz History</h3><table border='1' cellpadding='4'><tr><th>Quiz</th><th>Score (%)</th><th>Date</th></tr>";
            foreach ($usercontext['quiz_history'] as $q) {
                $html .= "<tr><td>".($q['itemname'] ?? '')."</td><td>{$q['score']}</td><td>{$q['time']}</td></tr>";
            }
            $html .= "</table>";
        }
        
        $pdf->writeHTML($html);
        $pdf->Output('individual_report.pdf', 'D');
        exit;
    }
}

// =========================================================================
// ✅ Step 3: Render the page if it's not a download request.
// =========================================================================
if ($nav === 'overview') {
    // The data is already prepared from Step 1
    $content = $OUTPUT->render_from_template('local_analytics/overview', $usercontext);
}
 elseif ($nav === 'reports') {
    // "Team report"
    $content = $OUTPUT->render_from_template('local_analytics/reports', []);
} elseif ($nav === 'organization_report') {
    $orgcontext = [
        'total_users' => 0,
        'status' => ['completed' => 0, 'inprogress' => 0, 'notstarted' => 0],
        'departments' => [],
        'team_members' => [],
        'rarely_started_courses' => [],
        'inprogress_count' => 0,
        'completed_count' => 0,
        'expired_count' => 0,
        'download_pdf_url' => new moodle_url('/local/analytics/index.php', ['nav'=>'organization_report','download'=>'pdf']),
        'download_excel_url' => new moodle_url('/local/analytics/index.php', ['nav'=>'organization_report','download'=>'excel']),
        'access_data' => [],
        'popular_courses' => [],
        'resume_courses' => []
    ];
    // --------------------------
    // Fetch total users and course status safely
    // --------------------------
    try {
        $orgcontext['total_users'] = (int) $DB->count_records_select('user', $user_where, $user_params);
        $sql_counts = "SELECT SUM(CASE WHEN cc.timecompleted > 0 THEN 1 ELSE 0 END) AS completed,
                              SUM(CASE WHEN cc.timecompleted = 0 THEN 1 ELSE 0 END) AS inprogress
                         FROM {course_completions} cc
                         JOIN {user} u ON u.id = cc.userid
                        WHERE $user_where";
        $params_counts = $user_params;
        $counts = $DB->get_record_sql($sql_counts, $params_counts);
        $orgcontext['completed_count']  = (int) ($counts->completed ?? 0);
        $orgcontext['inprogress_count'] = (int) ($counts->inprogress ?? 0);
        $orgcontext['notstarted'] = max(0, $orgcontext['total_users'] - $orgcontext['completed_count'] - $orgcontext['inprogress_count']);
        $orgcontext['status'] = [
            'completed' => $orgcontext['completed_count'],
            'inprogress' => $orgcontext['inprogress_count'],
            'notstarted' => $orgcontext['notstarted']
        ];
    } catch (\dml_exception $e) {
        debugging("Error fetching course status counts: " . $e->getMessage(), DEBUG_DEVELOPER);
    }
    // --------------------------
    // Department averages
    // --------------------------
    try {
        $dept_records = $DB->get_records_sql("
            SELECT COALESCE(u.profile_field_department, 'No Department') AS name,
                   AVG(gg.finalgrade / gi.grademax * 100) AS average_score
              FROM {user} u
              JOIN {grade_grades} gg ON gg.userid = u.id
              JOIN {grade_items} gi ON gi.id = gg.itemid
             WHERE $user_where AND gi.itemtype = 'mod' AND gi.itemmodule = 'quiz'
             GROUP BY u.profile_field_department
        ", $user_params) ?: [];
        foreach ($dept_records as $d) {
            $orgcontext['departments'][] = [
                'name' => $d->name ?? 'Unknown',
                'average_score' => round($d->average_score ?? 0, 2)
            ];
        }
    } catch (\dml_exception $e) {
        debugging("Error fetching department averages: " . $e->getMessage(), DEBUG_DEVELOPER);
    }
    // --------------------------
    // Rarely started courses
    // --------------------------
    $rarely_started_courses_sql = "
        SELECT c.fullname AS name,
               COUNT(b.id) AS bookmark_count,
               SUM(CASE WHEN ue.id IS NOT NULL THEN 1 ELSE 0 END) AS started_count
          FROM {course} c
     LEFT JOIN {local_bookmark} b ON b.courseid = c.id
     LEFT JOIN {enrol} en ON en.courseid = c.id
     LEFT JOIN {user_enrolments} ue ON ue.enrolid = en.id
     LEFT JOIN {user} u ON u.id = b.userid
      WHERE u.deleted = 0 AND (u.profile_field_department = :department OR :department IS NULL)";
    
    $rarely_started_courses_sql_group = "
        GROUP BY c.id, c.fullname
        HAVING COUNT(b.id) > 0 AND SUM(CASE WHEN ue.id IS NOT NULL THEN 1 ELSE 0 END) = 0
        ORDER BY bookmark_count DESC
        LIMIT 10
    ";
    
    $params = [];
    if (!$is_admin && $user_department) {
        $params['department'] = $user_department;
    } else {
        $params['department'] = null; // Use NULL for admin to bypass filter
    }
    
    try {
        $rarely_started_courses = $DB->get_records_sql($rarely_started_courses_sql . $rarely_started_courses_sql_group, $params) ?: [];
    
        $orgcontext['rarely_started_courses'] = array_map(function($c) {
            return [
                'name' => $c->name ?? 'Unknown',
                'bookmark_count' => (int)($c->bookmark_count ?? 0),
                'started_count' => (int)($c->started_count ?? 0),
            ];
        }, $rarely_started_courses);
    } catch (\dml_exception $e) {
        debugging("Error fetching rarely started courses: " . $e->getMessage(), DEBUG_DEVELOPER);
    }
    
    // --------------------------
    // Team members with courses
    // --------------------------
    try {
        $users = $DB->get_records_sql("SELECT * FROM {user} u WHERE $user_where", $user_params) ?: [];
        foreach ($users as $user) {
            $courses = [];
            $enrolled_courses = enrol_get_all_users_courses($user->id, true) ?: [];
            foreach ($enrolled_courses as $course) {
                try {
                    $info = new completion_info($course);
                    $status = ($info->is_enabled() && $info->is_course_complete($user->id)) ? 'Completed' : 'In Progress';
                    $avggrade = $DB->get_field_sql("
                        SELECT AVG(gg.finalgrade / gi.grademax * 100)
                          FROM {grade_grades} gg
                          JOIN {grade_items} gi ON gi.id = gg.itemid
                         WHERE gi.itemtype = 'mod'
                           AND gi.itemmodule = 'quiz'
                           AND gg.userid = :userid
                    ", ['userid' => $user->id]) ?: 0;
                    $courses[] = [
                        'name' => $course->fullname ?? 'Unknown',
                        'status' => $status,
                        'average_quiz' => round($avggrade, 2),
                        'lastaccess' => $user->lastaccess ? userdate($user->lastaccess) : get_string('never')
                    ];
                } catch (\Exception $e) {
                    debugging("Error fetching course data for user {$user->id}: " . $e->getMessage(), DEBUG_DEVELOPER);
                    continue;
                }
            }
            $orgcontext['team_members'][] = [
                'fullname' => fullname($user) ?: 'Unknown',
                'courses' => $courses
            ];
        }
    } catch (\Exception $e) {
        debugging("Error fetching team members: " . $e->getMessage(), DEBUG_DEVELOPER);
    }
    // Finally render template
    $content = $OUTPUT->render_from_template('local_analytics/organizationreport', $orgcontext);
} elseif ($nav === 'advanced_analytics') {
    $advcontext = [
        'popular_courses' => [],
        'department_completion' => [],
        'top_learners' => [],
        'lagging_learners' => [],
        'download_pdf_url' => new moodle_url('/local/analytics/index.php', ['nav'=>'advanced_analytics','download'=>'pdf']),
        'download_excel_url' => new moodle_url('/local/analytics/index.php', ['nav'=>'advanced_analytics','download'=>'excel'])
    ];
    global $DB;
    // --------------------------
    // Popular courses (by enrolment)
    // --------------------------
    // This query is global and does not need to be filtered by department.
    try {
        $popular_courses = $DB->get_records_sql("
            SELECT c.fullname AS name, COUNT(ue.userid) AS enrol_count
            FROM {course} c
            LEFT JOIN {enrol} e ON e.courseid = c.id
            LEFT JOIN {user_enrolments} ue ON ue.enrolid = e.id
            GROUP BY c.id, c.fullname
            ORDER BY enrol_count DESC
            LIMIT 10
        ");
        $advcontext['popular_courses'] = $popular_courses ?: [];
    } catch (\dml_exception $e) {
        debugging("Error fetching popular courses: " . $e->getMessage(), DEBUG_DEVELOPER);
    }
    // --------------------------
    // Department completion rates
    // --------------------------
    try {
        $dept_records_sql = "
            SELECT COALESCE(u.profile_field_department, 'No Department') AS name,
                   AVG(CASE WHEN cc.timecompleted > 0 THEN 1 ELSE 0 END) * 100 AS completion_rate
            FROM {user} u
            LEFT JOIN {course_completions} cc ON cc.userid = u.id
            WHERE $user_where
            GROUP BY name
        ";
        $dept_records = $DB->get_records_sql($dept_records_sql, $user_params);
        $advcontext['department_completion'] = $dept_records ?: [];
    } catch (\dml_exception $e) {
        debugging("Error fetching department completion rates: " . $e->getMessage(), DEBUG_DEVELOPER);
    }
    // --------------------------
    // Top learners (fast/high scores)
    // --------------------------
    try {
        $top_learners_sql = "
            SELECT u.id, u.firstname, u.lastname, AVG(gg.finalgrade / gi.grademax * 100) AS avg_score
            FROM {user} u
            JOIN {grade_grades} gg ON gg.userid = u.id
            JOIN {grade_items} gi ON gi.id = gg.itemid
            WHERE $user_where AND gi.itemtype='mod' AND gi.itemmodule='quiz'
            GROUP BY u.id, u.firstname, u.lastname
            ORDER BY avg_score DESC
            LIMIT 10
        ";
        $top_learners = $DB->get_records_sql($top_learners_sql, $user_params);
        $advcontext['top_learners'] = $top_learners ?: [];
    } catch (\dml_exception $e) {
        debugging("Error fetching top learners: " . $e->getMessage(), DEBUG_DEVELOPER);
    }
    // --------------------------
    // Lagging learners (never accessed or not started)
    // --------------------------
    try {
        $lagging_learners_sql = "
            SELECT DISTINCT u.id, u.firstname, u.lastname
            FROM {user} u
            LEFT JOIN {course_completions} cc ON cc.userid = u.id
            WHERE $user_where AND (u.lastaccess = 0 OR cc.timecompleted IS NULL)
            LIMIT 10
        ";
        $lagging_learners = $DB->get_records_sql($lagging_learners_sql, $user_params);
        $advcontext['lagging_learners'] = $lagging_learners ?: [];
    } catch (\dml_exception $e) {
        debugging("Error fetching lagging learners: " . $e->getMessage(), DEBUG_DEVELOPER);
    }
    // --------------------------
    // Render template
    // --------------------------
    $content = $OUTPUT->render_from_template('local_analytics/advancedanalytics', $advcontext);
} elseif ($nav === 'export_engine') {
    // Get selected scope from query param (default to 'individual')
    $selected_scope = optional_param('scope', 'individual', PARAM_ALPHA);
    // Prepare template context
    $exportcontext = [
        'export_scope' => [
            ['key'=>'individual','label'=>'เฉพาะบุคคล','selected'=>($selected_scope==='individual')],
            ['key'=>'department','label'=>'เฉพาะแผน','selected'=>($selected_scope==='department')]
        ],
        'download_csv_url'   => new moodle_url('/local/analytics/index.php', ['nav'=>'export_engine','download'=>'csv','scope'=>$selected_scope]),
        'download_excel_url' => new moodle_url('/local/analytics/index.php', ['nav'=>'export_engine','download'=>'excel','scope'=>$selected_scope]),
        'download_pdf_url'   => new moodle_url('/local/analytics/index.php', ['nav'=>'export_engine','download'=>'pdf','scope'=>$selected_scope]),
    ];
    // Handle actual download
    $downloadformat = optional_param('download', '', PARAM_ALPHA);
    if (in_array($downloadformat, ['csv','excel','pdf'])) {
        // Determine users based on scope
        if ($selected_scope === 'individual') {
            $users = [$USER]; // current user only
        } else {
            // Filter by department if not admin
            if ($is_admin) {
                $users = $DB->get_records('user', ['deleted'=>0]) ?: [];
            } else {
                $users = $DB->get_records('user', ['deleted'=>0,'profile_field_department'=>$user_department]) ?: [];
            }
        }
        // Build data array
        $data = [];
        foreach ($users as $u) {
            $enrolled = enrol_get_all_users_courses($u->id, true);
            $courses = [];
            foreach ($enrolled as $c) {
                $info = new completion_info($c);
                $status = ($info->is_enabled() && $info->is_course_complete($u->id)) ? 'Completed' : 'In Progress';
                $avg = $DB->get_field_sql("
                    SELECT AVG(gg.finalgrade / gi.grademax * 100)
                      FROM {grade_grades} gg
                      JOIN {grade_items} gi ON gi.id = gg.itemid
                     WHERE gi.itemtype='mod' AND gi.itemmodule='quiz'
                       AND gg.userid=:userid
                ", ['userid'=>$u->id]);
                $courses[] = $c->fullname . ' (' . round($avg ?? 0,2) . '% - '.$status.')';
            }
            $data[] = [
                'Fullname' => fullname($u),
                'Username' => $u->username,
                'Courses'  => implode(", ", $courses),
                'Last Access' => $u->lastaccess ? userdate($u->lastaccess) : get_string('never')
            ];
        }
        // Ensure $data is never empty
        if (empty($data)) {
            $data[] = ['Fullname'=>'No data','Username'=>'','Courses'=>'','Last Access'=>''];
        }
        // -----------------
        // CSV
        // -----------------
        if ($downloadformat === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename="export.csv"');
            $out = fopen('php://output','w');
            fputcsv($out, array_keys($data[0]));
            foreach ($data as $row) fputcsv($out, $row);
            fclose($out);
            exit;
        }
        // -----------------
        // Excel
        // -----------------
        if ($downloadformat === 'excel') {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray(array_keys($data[0]), NULL, 'A1');
            $row = 2;
            foreach ($data as $d) {
                $sheet->fromArray(array_values($d), NULL, "A$row");
                $row++;
            }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="export.xlsx"');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        }
        // -----------------
        // PDF
        // -----------------
        if ($downloadformat === 'pdf') {
            $pdf = new \TCPDF();
            $pdf->AddPage();
            $html = '<h1>Export Engine</h1><table border="1" cellpadding="4"><tr>';
            foreach (array_keys($data[0]) as $col) $html .= "<th>$col</th>";
            $html .= '</tr>';
            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($row as $v) $html .= "<td>$v</td>";
                $html .= '</tr>';
            }
            $html .= '</table>';
            $pdf->writeHTML($html);
            $pdf->Output('export.pdf', 'D');
            exit;
        }
    }
    // Render template
    $content = $OUTPUT->render_from_template('local_analytics/exportengine', $exportcontext);
} elseif ($nav === 'course_insights') {
    $insightscontext = [
        'saved_not_started_courses' => [],
    ];
    try {
        // Query for admins to see all
        $sql = "
            SELECT c.id, c.fullname,
                   COUNT(b.id) AS bookmark_count,
                   COUNT(e.userid) AS started_count
              FROM {course} c
         LEFT JOIN {local_bookmark} b ON b.courseid = c.id
         LEFT JOIN {enrol} en ON en.courseid = c.id
         LEFT JOIN {user_enrolments} e ON e.enrolid = en.id
          GROUP BY c.id
         HAVING COUNT(b.id) > 0 AND COUNT(e.userid) = 0
         ORDER BY bookmark_count DESC";

        $params = [];
        if (!$is_admin) {
            // For managers, filter by department
            $sql = "
                SELECT c.id, c.fullname,
                       COUNT(b.id) AS bookmark_count,
                       COUNT(e.userid) AS started_count
                  FROM {course} c
                  JOIN {local_bookmark} b ON b.courseid = c.id
                  JOIN {user} u ON u.id = b.userid
             LEFT JOIN {enrol} en ON en.courseid = c.id
             LEFT JOIN {user_enrolments} e ON e.enrolid = en.id
                 WHERE u.profile_field_department = :department
              GROUP BY c.id
              HAVING COUNT(b.id) > 0 AND COUNT(e.userid) = 0
              ORDER BY bookmark_count DESC";
            $params['department'] = $user_department;
        }

        $courses = $DB->get_records_sql($sql, $params) ?: [];
        $insightscontext['saved_not_started_courses'] = array_map(function($c) {
            return [
                'name' => $c->fullname ?? 'Unknown',
                'bookmark_count' => (int)($c->bookmark_count ?? 0),
                'started_count' => (int)($c->started_count ?? 0),
            ];
        }, $courses);
    } catch (\dml_exception $e) {
        debugging("Error fetching saved-but-not-started courses: " . $e->getMessage(), DEBUG_DEVELOPER);
    }
    $content = $OUTPUT->render_from_template('local_analytics/courseinsights', $insightscontext);
} else {
    $content = '';
}
// Render layout
$templatecontext = [
    'navitems' => $navitems,
    'content'  => $content,
];
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_analytics/layout', $templatecontext);
echo $OUTPUT->footer();