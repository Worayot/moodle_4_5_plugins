<?php
namespace local_bookmark\output;

defined('MOODLE_INTERNAL') || die();

use core_course_overview\course_overview;
use core_course\output\course_overview_renderer as base_course_overview_renderer;
use renderable;
use moodle_url;

class course_overview_override_renderer extends base_course_overview_renderer {

    protected function get_course_actions_menu(course_overview $course_overview) {
        global $USER, $DB;

        $menu = parent::get_course_actions_menu($course_overview);

        if (isloggedin() && !isguestuser()) {
            $courseid = $course_overview->id;
            $bookmarked = $DB->record_exists('local_bookmark', [
                'userid' => $USER->id,
                'courseid' => $courseid
            ]);

            // Initialize AMD module only once per page
            if (!$this->page->requires->is_amd_module_loaded('local_bookmark/init')) {
                $this->page->requires->js_call_amd('local_bookmark/init', 'init');
            }

            $text = $bookmarked ? get_string('unbookmarkcourse', 'local_bookmark')
                              : get_string('bookmarkcourse', 'local_bookmark');
            $icon_class = $bookmarked ? 'fa-star' : 'fa-star-o';

            $menu->add(
                $text,
                new moodle_url('#'),
                $icon_class,
                [
                    'data-courseid' => $courseid,
                    'data-action' => $bookmarked ? 'unbookmark' : 'bookmark',
                    'class' => 'local-bookmark-toggle-menuitem'
                ],
                true
            );                                                                                             }
                                                                                                           return $menu;
    }

    public function render_course_overview(course_overview $course_overview, renderable $parent) {
        return parent::render_course_overview($course_overview, $parent);
    }
}