define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {
    return {
        init: function() {
            $(document).on('click', '.local-bookmark-toggle-menuitem', function(e) {
                e.preventDefault();
                var $this = $(this);
                var courseid = $this.data('courseid');
                
                $this.prop('disabled', true);

                Str.get_strings([
                    {key: 'bookmarkremoved', component: 'local_bookmark'},
                    {key: 'errorbookmarkaction', component: 'local_bookmark'}
                ]).then(function(strings) {
                    Ajax.call([{
                        methodname: 'local_bookmark_toggle_bookmark',
                        args: { courseid: courseid },
                        done: function(response) {
    if (response.status === 'removed') {
        // Find the specific course element and remove it
        $('.bookmarked-course[data-courseid="'+courseid+'"]').fadeOut(300, function() {
            $(this).remove();
            
            // Show empty message if no courses left
            if ($('.bookmarked-course').length === 0) {
                $('.bookmarked-courses-list').html(
                    '<p class="alert alert-info">' + 
                    M.util.get_string('nobookmarks', 'local_bookmark') + 
                    '</p>'
                );
            }
        });
    }
},
                        fail: function(ex) {
                            Notification.addNotification({
                                message: strings[1],
                                type: 'error'
                            });
                        },
                        always: function() {
                            $this.prop('disabled', false);
                        }
                    }]);
                }).catch(Notification.exception);
            });
        }
    };
});