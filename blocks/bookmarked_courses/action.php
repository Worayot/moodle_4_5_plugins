<?php
require_once(__DIR__.'/../../config.php');
require_login();

$action = required_param('action', PARAM_ALPHA);
$courseid = required_param('courseid', PARAM_INT);
$returnurl = optional_param('returnurl', new moodle_url('/my'), PARAM_URL);

require_sesskey();

switch ($action) {
    case 'remove':
        $DB->delete_records('bookmark', [
            'userid' => $USER->id,
            'courseid' => $courseid
        ]);
        break;
}

redirect($returnurl);