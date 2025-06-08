<?php
$capabilities = [
    'local/bookmark:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => ['user' => CAP_ALLOW],
    ],
];
