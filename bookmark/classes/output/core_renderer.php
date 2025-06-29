<?php
namespace local_bookmark\output;

defined('MOODLE_INTERNAL') || die();

use moodle_url;
use html_writer;

class core_renderer extends \core_renderer {

    public function standard_footer_html() {
        $footer = parent::standard_footer_html();

        global $COURSE, $USER, $DB;
        if (isguestuser() || !isloggedin()) {
            return $footer;
        }

        $context = \context_course::instance($COURSE->id);

        $bookmarked = $DB->record_exists('local_bookmark', ['userid' => $USER->id, 'courseid' => $COURSE->id]);

        $buttontext = $bookmarked ? get_string('removebookmark', 'local_bookmark') : get_string('addbookmark', 'local_bookmark');

        $url = new moodle_url('/local/bookmark/bookmark.php', [
            'courseid' => $COURSE->id,
            'sesskey' => sesskey(),
        ]);

        $button = html_writer::link($url, $buttontext, ['class' => 'btn btn-secondary local-bookmark-button']);

        return $footer . html_writer::div($button, 'local-bookmark-button');
    }
}
