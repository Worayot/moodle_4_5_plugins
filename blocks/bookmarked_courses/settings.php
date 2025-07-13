<?php
/**
 * Settings for bookmarked courses block.
 *
 * @package    block_bookmarked_courses
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Maximum number of courses to display
    $settings->add(new admin_setting_configtext(
        'block_bookmarked_courses/maxcourses',
        get_string('maxcourses', 'block_bookmarked_courses'),
        get_string('maxcoursesdesc', 'block_bookmarked_courses'),
        10,
        PARAM_INT
    ));

    // Show course images
    $settings->add(new admin_setting_configcheckbox(
        'block_bookmarked_courses/showimages',
        get_string('showimages', 'block_bookmarked_courses'),
        get_string('showimagesdesc', 'block_bookmarked_courses'),
        1
    ));

    // Show remove button
    $settings->add(new admin_setting_configcheckbox(
        'block_bookmarked_courses/showremove',
        get_string('showremove', 'block_bookmarked_courses'),
        get_string('showremovedesc', 'block_bookmarked_courses'),
        1
    ));
}