<?php
require_once(__DIR__ . '/../../config.php');
require_login();

global $USER, $DB, $OUTPUT, $PAGE;

$userid = required_param('userid', PARAM_INT);

if ($USER->id !== $userid && !is_siteadmin()) {
    throw new moodle_exception('nopermissions', 'error', '', 'view user bookmarks');
}

// ✅ Required for correct Moodle rendering
$PAGE->set_url(new moodle_url('/local/bookmark/index.php', ['userid' => $userid]));
$PAGE->set_context(context_user::instance($userid)); // or use context_system::instance()
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
        'unbookmarkurl' => (new moodle_url('/local/bookmark/bookmark.php', [
            'courseid' => $record->id,
            'sesskey' => sesskey()
        ]))->out(false)
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_bookmark/bookmarked_courses', ['courses' => $courses]);
echo $OUTPUT->footer();
