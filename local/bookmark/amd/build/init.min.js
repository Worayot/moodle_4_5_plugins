define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {
    var initialized = false;

    return {
        init: function() {
            if (initialized) {
                return;
            }
            initialized = true;

            var stringsPromise = Str.get_strings([
                {key: 'bookmarkadded', component: 'local_bookmark'},
                {key: 'bookmarkremoved', component: 'local_bookmark'},
                {key: 'unbookmarkcourse', component: 'local_bookmark'},
                {key: 'bookmarkcourse', component: 'local_bookmark'},
                {key: 'errorbookmarkaction', component: 'local_bookmark'}
            ]);

            $(document).on('click', '.local-bookmark-toggle-menuitem', function(e) {
                e.preventDefault();
                var $this = $(this);
                var courseid = $this.data('courseid');

                $this.addClass('disabled').attr('aria-disabled', 'true');

                stringsPromise.then(function(strings) {
                    Ajax.call([{
                        methodname: 'local_bookmark_toggle_bookmark',
                        args: { courseid: courseid },
                        done: function(response) {
                            if (!response) {
                                Notification.addNotification({
                                    message: strings[4] + ' Unexpected response.',
                                    type: 'error'
                                });
                                return;
                            }

                            var message = response.status === 'added' ? strings[0] : strings[1];
                            Notification.addNotification({
                                message: message,
                                type: 'success'
                            });

                            if (response.status === 'added') {
                                $this.data('action', 'unbookmark')
                                    .find('i').removeClass('fa-star-o').addClass('fa-star')
                                    .end().text(strings[3]);
                            } else {
                                $this.data('action', 'bookmark')
                                    .find('i').removeClass('fa-star').addClass('fa-star-o')
                                    .end().text(strings[2]);
                            }
                        },
                        fail: Notification.exception,
                        always: function() {
                            $this.removeClass('disabled').attr('aria-disabled', 'false');
                        }
                    }]);
                }).catch(Notification.exception);
            });
        }
    };
});