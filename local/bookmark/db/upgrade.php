<?php
defined('MOODLE_INTERNAL') || die();

function xmldb_local_bookmark_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2025060814) {
        // This will be called again after install anyway
        try {
            local_bookmark_register_hook_callbacks();
        } catch (\Exception $e) {
            // Don't fail the upgrade if hooks aren't ready yet
            debugging('Hook registration failed during upgrade: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        upgrade_plugin_savepoint(true, 2025060814, 'local', 'bookmark');
    }

    return true;
}