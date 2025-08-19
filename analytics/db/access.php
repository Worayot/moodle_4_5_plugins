<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/analytics:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'admin' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/course:view'
    ],
];