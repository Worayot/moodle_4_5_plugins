<?php
require_once(__DIR__ . '/../../config.php');

global $DB, $USER;

$courseid = required_param('courseid', PARAM_INT);
$sesskey = required_param('sesskey', PARAM_ALPHANUM);
$redirect = optional_param('redirect', '', PARAM_LOCALURL);  // Add this line

require_login($courseid);
require_sesskey();

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

$record = $DB->get_record('local_bookmark', ['userid' => $USER->id, 'courseid' => $courseid]);
if ($record) {
    $DB->delete_records('local_bookmark', ['id' => $record->id]);
    \core\session\manager::write_close();
    $destination = $redirect ?: new moodle_url('/course/view.php', ['id' => $courseid]);
    redirect($destination, get_string('bookmarkremoved', 'local_bookmark'));
} else {
    $newrecord = new stdClass();
    $newrecord->userid = $USER->id;
    $newrecord->courseid = $courseid;
    $newrecord->timecreated = time();
    $DB->insert_record('local_bookmark', $newrecord);
    \core\session\manager::write_close();
    redirect($redirect ?: new moodle_url('/course/view.php', ['id' => $courseid]), get_string('bookmarkadded', 'local_bookmark'));
}
