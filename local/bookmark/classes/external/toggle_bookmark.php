<?php
namespace local_bookmark\external;

use external_function_parameters;                                                                  use external_value;
use external_api;
use external_single_structure;

defined('MOODLE_INTERNAL') || die();

class toggle_bookmark extends external_api {
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID')
        ]);
    }

    public static function execute($courseid) {
        global $USER, $DB;
        
        $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);
        debugging("Bookmark toggle called for course $courseid by user $USER->id", DEBUG_DEVELOPER);
        
        $record = $DB->get_record('local_bookmark', [
            'userid' => $USER->id, 
            'courseid' => $params['courseid']
        ]);

         if ($record) {
            $DB->delete_records('local_bookmark', ['id' => $record->id]);
            debugging("Bookmark removed", DEBUG_DEVELOPER);
            return ['status' => 'removed', 'courseid' => $params['courseid']];
        } else {
            $newrecord = (object)[
                'userid' => $USER->id,
                'courseid' => $params['courseid'],
                'timecreated' => time()
            ];
            $DB->insert_record('local_bookmark', $newrecord);
            return ['status' => 'added', 'courseid' => $params['courseid']];
        }
    }

    public static function execute_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Status of bookmark toggle')
        ]);
    }
}
