<?php
$functions = [
    'local_bookmark_toggle_bookmark' => [
        'classname' => 'local_bookmark\external\toggle_bookmark',
        'methodname' => 'execute',
        'classpath' => '',
        'description' => 'Toggle bookmark status',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/bookmark:togglebookmark',
    ]
];

$services = [
    'Local Bookmark Service' => [
        'functions' => ['local_bookmark_toggle_bookmark'],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];