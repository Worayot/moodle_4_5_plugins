<?php
namespace local_bookmark\hook;

use core\hook\output\before_http_headers;

defined('MOODLE_INTERNAL') || die();

class before_http_headers_listener {
    public function __invoke(before_http_headers $hook): void {
        global $PAGE;

        // Only add CSS on profile pages
        if (strpos($PAGE->url->get_path(), '/user/profile.php') !== false) {
            $PAGE->requires->css('/local/bookmark/styles.css');
        }
    }
}
