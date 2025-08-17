<?php
$string['pluginname'] = 'Analytics';
$string['local/analytics:view'] = 'View Analytics page';
$string['privacy:metadata'] = 'The Analytics plugin does not store any personal data.';
$string['overview'] = 'Overview';
$string['reports'] = 'Reports';
$string['settings'] = 'Settings';
$string['individualreport'] = 'Individual Report';
$string['teamreport'] = 'Team Report';
$string['download_pdf'] = 'Download PDF';
$string['download_excel'] = 'Download Excel';
$string['organizationreport'] = 'Organization Report';
$string['download'] = 'Download';
$string['download_pdf_url'] = 'Download PDF URL';
$string['advancedanalytics'] = 'Advanced Analytics';
$string['exportengine'] = 'Export Engine';
$selected_scope = optional_param('scope', 'individual', PARAM_ALPHA);
$exportcontext = [
    'export_scope' => [
        ['key'=>'individual', 'label'=>'เฉพาะบุคคล', 'selected'=>($selected_scope==='individual')],
        ['key'=>'department', 'label'=>'เฉพาะแผน', 'selected'=>($selected_scope==='department')]
    ]
];
$string['courseinsights'] = 'Course Insights';