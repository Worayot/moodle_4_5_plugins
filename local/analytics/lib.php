<?php
// local/analytics/lib.php
defined('MOODLE_INTERNAL') || die();
/**
 * Extends the front page navigation menu.
 */
function local_analytics_extend_navigation_frontpage($navigation) {
    global $USER;
    if (!isloggedin() || isguestuser()) {
        return;
    }

    if (has_capability('local/analytics:view', context_system::instance(), $USER->id)) {
        // This link is for the global analytics page.
        $url = new moodle_url('/local/analytics/index.php');
        $node = navigation_node::create(
            get_string('pluginname', 'local_analytics'),
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'local_analytics_dashboard',
            new pix_icon('i/chart', '')
        );
        $navigation->add_node($node);
    }
}