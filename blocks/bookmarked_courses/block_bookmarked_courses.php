<?php
require_once($CFG->dirroot . '/local/bookmark/lib.php');

/**
 * Bookmarked courses block
 *
 * @package    block_bookmarked_courses
 */
class block_bookmarked_courses extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_bookmarked_courses');
    }

    public function get_content() {
        global $USER, $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        // ✅ Fetch from local_bookmark
        $records = $DB->get_records_sql("
            SELECT c.*
            FROM {local_bookmark} b
            JOIN {course} c ON b.courseid = c.id
            WHERE b.userid = :userid
            ORDER BY b.timecreated DESC
        ", ['userid' => $USER->id]);

        // ✅ Convert to rendered data
        $bookmarks = array_map('local_bookmark_prepare_course_data', $records);

        if (empty($bookmarks)) {
            $this->content->text = get_string('nobookmarks', 'block_bookmarked_courses');
            return $this->content;
        }

        // ✅ Render bookmarks
        $this->content->text .= html_writer::start_div('bookmarked-courses-list');

        foreach ($bookmarks as $course) {
            $courseurl = $course['viewurl'];
            $fullname = $course['fullname'];
            $unbookmarkurl = $course['unbookmarkurl'];

            $this->content->text .= html_writer::start_div('bookmarked-course');

            $this->content->text .= html_writer::link(
                $courseurl,
                format_string($fullname),
                ['class' => 'course-link']
            );

            $this->content->text .= html_writer::link(
                $unbookmarkurl,
                $OUTPUT->pix_icon('t/delete', get_string('removebookmark', 'local_bookmark')),
                ['class' => 'remove-bookmark']
            );

            $this->content->text .= html_writer::end_div();
        }

        $this->content->text .= html_writer::end_div();

        return $this->content;
    }

    public function applicable_formats() {
        return ['my' => true, 'site' => true];
    }

    public function instance_allow_multiple() {
        return false;
    }
}
