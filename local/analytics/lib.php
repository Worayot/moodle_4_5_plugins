<?php
// local/analytics/lib.php
defined('MOODLE_INTERNAL') || die();
function local_analytics_before_standard_html_head() {
    global $USER, $PAGE;

    if (!isloggedin() || isguestuser()) {
        return;
    }

    if (has_capability('local/analytics:view', context_system::instance(), $USER->id)) {
        $url  = new moodle_url('/local/analytics/index.php');
        $text = get_string('pluginname', 'local_analytics');
        
        $js = "
            document.addEventListener('DOMContentLoaded', function() {
                var ul = document.querySelector('.primary-navigation nav ul');
                if (!ul) return;

                var li = document.createElement('li');
                li.className = 'nav-item';
                li.setAttribute('role', 'none');
                li.setAttribute('data-key', 'local_analytics_dashboard');
                li.setAttribute('data-forceintomoremenu', 'false');

                var a = document.createElement('a');
                a.className = 'nav-link';
                a.setAttribute('role', 'menuitem');
                a.href = " . json_encode($url->out(false)) . ";
                a.textContent = " . json_encode($text) . ";
                a.setAttribute('data-disableactive', 'true');

                if (window.location.pathname.indexOf('/local/analytics/index.php') !== -1) {
                    document.querySelectorAll('.primary-navigation .nav-link.active')
                        .forEach(function(link) { link.classList.remove('active'); });
                    a.classList.add('active');
                }

                li.appendChild(a);

                // Insert right after Home
                var homeItem = ul.querySelector('[data-key=\"home\"]');
                if (homeItem && homeItem.nextSibling) {
                    homeItem.after(li);
                } else {
                    ul.appendChild(li);
                }
            });
        ";

       
        $PAGE->requires->js_init_code($js);
    }
}