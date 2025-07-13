<?php
// Get bookmarked courses
// require_once($CFG->dirroot . '/local/bookmark/lib.php');

// $records = $DB->get_records_sql("
//     SELECT c.*
//     FROM {local_bookmark} b
//     JOIN {course} c ON b.courseid = c.id
//     WHERE b.userid = :userid
//     ORDER BY b.timecreated DESC
// ", ['userid' => $USER->id]);

// $bookmarks = array_map('local_bookmark_prepare_course_data', $records);
