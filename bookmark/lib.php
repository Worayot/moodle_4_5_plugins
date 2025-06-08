<?php
function local_bookmark_extend_navigation_user($navigation, $user, $usercontext, $course, $context) {
    global $PAGE, $USER;

    // Only show for the logged-in user viewing their own profile
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

function local_bookmark_plugin_renderer_factory($component, $page, $target) {
    if ($component === 'core') {
        return new \local_bookmark\output\core_renderer($page, $target);
    }
    return null;
}

function local_bookmark_extend_navigation_course($navigation, $course, $context) {
    global $USER, $DB;

    if (isguestuser() || !isloggedin()) {
        return;
    }

    // Check if this course is bookmarked by the current user
    $bookmarked = $DB->record_exists('local_bookmark', ['userid' => $USER->id, 'courseid' => $course->id]);

    $buttontext = $bookmarked ? get_string('removebookmark', 'local_bookmark') : get_string('addbookmark', 'local_bookmark');
    $url = new moodle_url('/local/bookmark/bookmark.php', [
        'courseid' => $course->id,
        'sesskey' => sesskey()
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
    global $PAGE;
    $url = new moodle_url('/local/bookmark/index.php', ['userid' => $user->id]);
    $node = new core_user\output\myprofile\node('miscellaneous', 'bookmarkedcourses', get_string('bookmarkedcourses', 'local_bookmark'), null, $url);
    $tree->add_node($node);
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

