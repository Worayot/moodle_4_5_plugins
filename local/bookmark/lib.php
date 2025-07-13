<?php

defined('MOODLE_INTERNAL') || die();

function local_bookmark_register_hook_callbacks(): void {
    // Only register if the hook system is available
    if (class_exists('\core\hook\dispatcher_interface')) {
        try {
            \core\di::get(\core\hook\dispatcher_interface::class)
                ->register_listener(
                    \local_bookmark\hook\before_http_headers_listener::class,
                    \core\hook\output\before_http_headers::class
                );
        } catch (\Exception $e) {
            // Silently fail during installation/upgrade
            debugging('Failed to register hook: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
}

function local_bookmark_extend_navigation_user($navigation, $user, $usercontext, $course, $context) {
    global $USER;

    if ($USER->id !== $user->id) {
        return;
    }

    $url = new moodle_url('/local/bookmark/index.php', ['userid' => $user->id]);
    $node = navigation_node::create(
        get_string('pluginname', 'local_bookmark'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_bookmark',
        new pix_icon('i/star', '')
    );
    $navigation->add_node($node);
}

/**
 * Returns a custom renderer for specific components.
 * @param string $component
 * @param \moodle_page $page
 * @param string $target
 * @return \core_output\renderer|null
 */
function local_bookmark_plugin_renderer_factory($component, $page, $target) {
    // --- DEBUG START ---
    error_log("DEBUG: local_bookmark_plugin_renderer_factory called for component: " . $component);
    // --- DEBUG END ---

    // If you want to override the core course overview renderer for /my/courses.php etc.
    if ($component === 'core_course') {
        error_log("DEBUG: local_bookmark_plugin_renderer_factory: Overriding core_course renderer.");
        require_once(__DIR__ . '/classes/output/course_overview_override_renderer.php'); // Make sure this path is correct
        return new \local_bookmark\output\course_overview_override_renderer($page, $target);
    }

    return null;
}

function local_bookmark_extend_navigation_course($navigation, $course, $context) {
    global $USER, $DB;

    if (isguestuser() || !isloggedin()) {
        return;
    }

    $bookmarked = $DB->record_exists('local_bookmark', ['userid' => $USER->id, 'courseid' => $course->id]);
    $buttontext = $bookmarked ? get_string('removebookmark', 'local_bookmark') : get_string('addbookmark', 'local_bookmark');
    $url = new moodle_url('/local/bookmark/bookmark.php', [
        'courseid' => $course->id,
        'sesskey' => sesskey(),
    ]);

    $node = navigation_node::create(
        $buttontext,
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'local_bookmark_toggle',
        new pix_icon('i/star', '')
    );

    $navigation->add_node($node);
}

function local_bookmark_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $DB, $PAGE;

    // Only show if the user has bookmarks or it's the current user's profile
    $hasbookmarks = $DB->record_exists('local_bookmark', ['userid' => $user->id]);
    if (!$hasbookmarks && !$iscurrentuser) {
        return;
    }

    // Create the category directly (older Moodle versions)
    $categoryname = 'local_bookmark';
    $category = new core_user\output\myprofile\category($categoryname, get_string('bookmarks', 'local_bookmark'));
    $tree->add_category($category);

    // Add our content as a node
    $url = new moodle_url('/local/bookmark/index.php', ['userid' => $user->id]);

    // Get bookmarked courses
    $records = $DB->get_records_sql("
        SELECT c.id, c.fullname
        FROM {local_bookmark} b
        JOIN {course} c ON b.courseid = c.id
        WHERE b.userid = :userid
        ORDER BY b.timecreated DESC
    ", ['userid' => $user->id]);

    if (!empty($records)) {
        $content = html_writer::start_tag('ul', ['class' => 'bookmarked-courses-list']);
        foreach ($records as $course) {
            $courseurl = new moodle_url('/course/view.php', ['id' => $course->id]);
            $content .= html_writer::tag('li',
                html_writer::link($courseurl, format_string($course->fullname)),
                ['class' => 'bookmarked-course']
            );
        }
        $content .= html_writer::end_tag('ul');

        // Add "View all" link
        $content .= html_writer::div(
            html_writer::link($url, get_string('viewallbookmarks', 'local_bookmark')),
            'view-all-bookmarks'
        );
    } else {
        $content = html_writer::div(
            get_string('nobookmarkedcourses', 'local_bookmark'),
            'no-bookmarks'
        );
    }

    $node = new core_user\output\myprofile\node(
        $categoryname,
        'bookmarkedcourses',
        get_string('bookmarkedcourses', 'local_bookmark'),
        null,
        null,
        $content
    );
    $category->add_node($node);
}

function local_bookmark_extend_navigation_frontpage($navigation) {
    global $USER;

    if (!isloggedin() || isguestuser()) return;

    $url = new moodle_url('/local/bookmark/index.php', ['userid' => $USER->id]);
    $node = navigation_node::create(
        get_string('bookmarkedcourses', 'local_bookmark'),
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'local_bookmark_dashboard',
        new pix_icon('i/star', '')
    );
    $navigation->add_node($node);
}

function local_bookmark_course_list_item($course) {
    global $USER, $DB;

    $bookmarked = $DB->record_exists('local_bookmark', ['userid' => $USER->id, 'courseid' => $course->id]);

    return [
        'id' => $course->id,
        'fullname' => format_string($course->fullname),
        'viewurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(),
        'bookmarked' => $bookmarked,
        'bookmarkurl' => (new moodle_url('/local/bookmark/bookmark.php', [
            'courseid' => $course->id,
            'sesskey' => sesskey(),
            'redirect' => (new moodle_url('/local/bookmark/index.php', ['userid' => $USER->id]))->out(false),
        ]))->out(false),
        'unbookmarkurl' => (new moodle_url('/local/bookmark/bookmark.php', [
            'courseid' => $course->id,
            'sesskey' => sesskey(),
        ]))->out(false),
    ];
}

function local_bookmark_prepare_course_data($course) {
    global $USER, $DB;

    $bookmarked = $DB->record_exists('local_bookmark', ['userid' => $USER->id, 'courseid' => $course->id]);
    $redirecturl = new moodle_url('/local/bookmark/index.php', ['userid' => $USER->id]);

    return [
        'id' => $course->id,
        'fullname' => format_string($course->fullname),
        'viewurl' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(),
        'bookmarked' => $bookmarked,
        'bookmarkurl' => (new moodle_url('/local/bookmark/bookmark.php', [
            'courseid' => $course->id,
            'sesskey' => sesskey(),
            'redirect' => $redirecturl->out(false),
        ]))->out(false),
        'unbookmarkurl' => (new moodle_url('/local/bookmark/bookmark.php', [
            'courseid' => $course->id,
            'sesskey' => sesskey(),
            'redirect' => $redirecturl->out(false),
        ]))->out(false),
    ];
}
