<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_bookmark_install() {
    require_once(__DIR__.'/../lib.php');
    local_bookmark_register_hook_callbacks();
}