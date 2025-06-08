<?php
namespace local_bookmark\external;

use external_function_parameters;
use external_value;
use external_api;
use core_user;

class toggle_bookmark extends external_api {
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID')
        ]);
    }

    public static function execute($courseid) {
        global $USER, $DB, $CFG;
        require_once($CFG->libdir . '/dml/moodle_database.php');

        $record = $DB->get_record('local_bookmark', ['userid' => $USER->id, 'courseid' => $courseid]);

        if ($record) {
            $DB->delete_records('local_bookmark', ['id' => $record->id]);
            return ['status' => 'removed'];
        } else {
            $DB->insert_record('local_bookmark', [
                'userid' => $USER->id,
                'courseid' => $courseid,
                'timecreated' => time()
            ]);
            return ['status' => 'added'];
        }
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status of bookmark toggle')
        ]);
    }
}
