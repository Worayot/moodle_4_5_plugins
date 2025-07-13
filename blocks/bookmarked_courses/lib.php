<?php
/**
 * Library of functions for bookmarked courses block.
 *
 * @package    block_bookmarked_courses
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add bookmarked courses block to dashboard.
 *
 * @param int $userid
 * @return bool
 */
function block_bookmarked_courses_add_to_dashboard($userid) {
    global $DB, $PAGE;

    if ($PAGE->pagetype != 'my-index') {
        return false;
    }

    $context = context_user::instance($userid);
    $page = new moodle_page();
    $page->set_context($context);
    $page->blocks->add_region('content');
    $page->blocks->add_block('bookmarked_courses', 'content', 0, false, 'my-index');

    return true;
}

/**
 * Check if a course is bookmarked by user.
 *
 * @param int $userid
 * @param int $courseid
 * @return bool
 */
function block_bookmarked_courses_is_bookmarked($userid, $courseid) {
    global $DB;
    return $DB->record_exists('bookmark', ['userid' => $userid, 'courseid' => $courseid]);
}

/**
 * Add a course to user's bookmarks.
 *
 * @param int $userid
 * @param int $courseid
 * @return bool
 */
function block_bookmarked_courses_add_bookmark($userid, $courseid) {
    global $DB;

    if (block_bookmarked_courses_is_bookmarked($userid, $courseid)) {
        return false;
    }

    $bookmark = new stdClass();
    $bookmark->userid = $userid;
    $bookmark->courseid = $courseid;
    $bookmark->timecreated = time();

    return $DB->insert_record('bookmark', $bookmark);
}

/**
 * Remove a course from user's bookmarks.
 *
 * @param int $userid
 * @param int $courseid
 * @return bool
 */
function block_bookmarked_courses_remove_bookmark($userid, $courseid) {
    global $DB;
    return $DB->delete_records('bookmark', ['userid' => $userid, 'courseid' => $courseid]);
}