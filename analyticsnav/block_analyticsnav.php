<?php
defined('MOODLE_INTERNAL') || die();

class block_analyticsnav extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_analyticsnav');
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $url = new moodle_url('/local/analytics/index.php'); // or your insights page
        $this->content->text = html_writer::link($url, get_string('gotopage', 'block_analyticsnav'));

        return $this->content;
    }
}
