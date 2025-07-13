<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'block/bookmarked_courses:myaddinstance' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'user' => CAP_ALLOW
        ],
    ],
    
    'block/bookmarked_courses:addinstance' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => [
            'manager' => CAP_ALLOW
        ],
    ],
];