<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_login();

global $USER, $DB, $OUTPUT, $PAGE;

$PAGE->requires->js_call_amd('local_bookmark/init', 'init');

$userid = $USER->id; // Always use the currently logged-in user's ID

// Check capability for current user (the one logged in, not $userid param)
if (!has_capability('local/bookmark:view', context_user::instance($USER->id))) {
    throw new moodle_exception('nopermissions', 'error', '', 'view bookmarks capability');
}

$PAGE->set_url(new moodle_url('/local/bookmark/index.php'));
$PAGE->set_context(context_user::instance($userid));
$PAGE->set_title(get_string('bookmarkedcourses', 'local_bookmark'));
$PAGE->set_heading(get_string('bookmarkedcourses', 'local_bookmark'));

$records = $DB->get_records_sql("
    SELECT c.*
    FROM {local_bookmark} b
    JOIN {course} c ON b.courseid = c.id
    WHERE b.userid = :userid
", ['userid' => $userid]);

$courses = [];
foreach ($records as $record) {
   $courses[] = [
    'id' => $record->id,
    'fullname' => format_string($record->fullname),
    'viewurl' => (new moodle_url('/course/view.php', ['id' => $record->id]))->out(),
    'bookmarked' => true,
    'bookmarkurl' => (new moodle_url('/local/bookmark/bookmark.php', [
        'courseid' => $record->id,
        'sesskey' => sesskey(),
    ]))->out(false),
    'unbookmarkurl' => (new moodle_url('/local/bookmark/bookmark.php', [
        'courseid' => $record->id,
        'sesskey' => sesskey(),
    ]))->out(false),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_bookmark/bookmarked_courses', ['courses' => $courses]);
echo $OUTPUT->footer();