jQuery(function($) {
    let LogtivityLogIndex = {
        init: function() {
            this.container = $('#logtivity-log-index');

            if (this.container.length) {
                this.form = $('#logtivity-log-index-search-form');
                this.listenForPagination();
                this.listenForChange();
                this.filter();
                this.listenForViewLog()
                this.listenForCloseModal();
            }
        },

        listenForCloseModal: function() {
            let listenForCloseModal = this;

            $('body').on('click', '.js-logtivity-notice-dismiss', function(e) {
                e.preventDefault();

                listenForCloseModal.hideModal();
            });

            $(document).on('keyup', function(e) {
                if (e.key === 'Escape' && listenForCloseModal.modalOpen) {
                    listenForCloseModal.hideModal();
                }

            });

            $(document).on('keydown', function(e) {
                if (e.key !== 'Tab' || !listenForCloseModal.modalOpen) {
                    return;
                }

                let $focusable = $('#logtivity-log-modal')
                    .find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')
                    .filter(':visible');

                if (!$focusable.length) {
                    return;
                }

                let firstFocusable = $focusable[0],
                    lastFocusable  = $focusable[$focusable.length - 1];

                if (e.shiftKey && document.activeElement === firstFocusable) {
                    e.preventDefault();
                    lastFocusable.focus();
                } else if (!e.shiftKey && document.activeElement === lastFocusable) {
                    e.preventDefault();
                    firstFocusable.focus();
                }
            });

            $(document).mouseup(function(e) {
                if (!listenForCloseModal.modalOpen) {
                    return;
                }

                let $container = $('.logtivity-modal-dialog');

                // if the target of the click isn't the container nor a descendant of the container
                if (!$container.is(e.target) && $container.has(e.target).length === 0) {
                    listenForCloseModal.hideModal();
                }
            });
        },

        listenForViewLog: function() {
            let listenForViewLog = this;

            $('body').on('click', '.js-logtivity-view-log', function(e) {
                e.preventDefault();

                listenForViewLog.lastFocusedElement = this;
                listenForViewLog.showLogModal($(this).next().html());
            });
        },

        showLogModal: function(modalContent) {
            $('.logtivity-modal').attr('aria-hidden', 'false').addClass('active');

            this.modalOpen = true;

            $('.logtivity-modal-content').html(modalContent);
            $('.js-logtivity-notice-dismiss').trigger('focus');
        },

        hideModal: function() {
            $('.logtivity-modal').attr('aria-hidden', 'true').removeClass('active');

            this.modalOpen = false;

            if (this.lastFocusedElement) {
                this.lastFocusedElement.focus();
            }
        },

        listenForChange: function() {
            let listenForChange = this,
                timeout         = null;

            $('body').on('input', '#logtivity-log-index-search-form input', function(e) {
                e.preventDefault();

                $('#logtivity_page').val('');

                // Clear the timeout if it has already been set.
                // This will prevent the previous task from executing
                // if it has been less than <MILLISECONDS>
                clearTimeout(timeout);

                // Make a new timeout set to go off in 1000ms
                timeout = setTimeout(function() {
                    listenForChange.filter();
                }, 1000);
            });
        },

        loading: function() {
            let $loading = $('<div role="status" style="text-align: center; padding-bottom: 20px">');

            $loading.append(
                '<div class="spinner is-active" style="float:none;width:auto;height:auto;padding:10px 0 10px 50px;background-position:20px 0;"></div>'
            );
            $loading.append(
                $('<span class="screen-reader-text">').text(logtivityAdminA11y.loadingLogs)
            );

            this.container.attr('aria-busy', 'true').empty().append($loading);
        },

        listenForPagination: function() {
            let listenForPagination = this;

            $('body').on('click', '.js-logtivity-pagination', function(e) {
                e.preventDefault();

                $('#logtivity_page').val($(this).attr('data-page'));

                listenForPagination.filter();
            });
        },

        filter: function() {
            let filter = this;

            this.loading();

            $.ajax({
                url    : filter.form.attr('action'),
                type   : 'GET',
                data   : filter.form.serialize(),
                success: function(result) {
                    filter.container.attr('aria-busy', 'false').html(result.view);
                },
                error  : function(error) {
                    let $error = $('<div class="notice notice-error" role="alert"><p></p></div>');

                    $error.find('p').text(logtivityAdminA11y.loadLogsError);
                    filter.container.attr('aria-busy', 'false').empty().append($error);
                }
            });
        }
    };

    let DismissUrlHasChanged = {
        init: function() {
            $(document).on('click', '.notice-dismiss', function() {
                let type         = $(this).closest('.is-dismissible').attr('notice'),
                    dismissUntil = $(this).closest('.is-dismissible').attr('dismiss-until');

                if (type) {
                    $.ajax(ajaxurl, {
                        type: 'POST',
                        data: {
                            action      : 'logtivity_dismiss_notice',
                            _ajax_nonce : logtivityAdmin.dismissNonce,
                            postType    : type,
                            dismissUntil: dismissUntil,
                        }
                    });
                }
            });
        }
    };

    LogtivityLogIndex.init();
    DismissUrlHasChanged.init();

    let registerMessage = function(message, type) {
        type = type || 'info';

        let $messaging = $('#logtivity-register-response')
            .removeClass()
            .addClass('logtivity-notice logtivity-notice-' + type)
            .css('width', 'fit-content');

        if (message) {
            $messaging
                .css('display', 'block')
                .html('<div>' + message + '</div>');
        } else {
            $messaging
                .css('display', 'none')
                .html();
        }
    };

    $('#logtivity-register-site').on('submit', function(evt) {
        evt.preventDefault();

        registerMessage();

        $.post(this.getAttribute('action'), $(this).serialize())
            .success(function(response) {
                let success      = response.success || false,
                    responseData = response.data || [],
                    code         = responseData.code || 200;

                console.log(responseData);
                console.log(response);

                if (success && code === 200) {
                    registerMessage(responseData.message);

                } else if (success) {
                    let message = '<h2>' + code + ': ' + responseData.error + '<h2>';
                    if (responseData.error !== responseData.message) {
                        message += responseData.message;
                    }

                    registerMessage(message, 'danger');

                } else if (typeof responseData == 'string') {
                    registerMessage(responseData, 'danger');

                } else if (typeof responseData.forEach !== 'undefined') {
                    let message = '';
                    responseData.forEach(function(error) {
                        message += '<p>' + error.message + '</p>';
                    });
                    registerMessage(message, 'danger');

                } else {
                    console.log(response);
                    registerMessage('Unknown Response', 'danger')
                }

            })
            .error(function($xhr) {
                let message = 'Unknown Error'
                if ($xhr.responseJSON) {
                    message = $xhr.responseJSON.data || message;
                } else if ($xhr.responseText) {
                    message = $xhr.responseText;
                }

                registerMessage(message, 'danger');
            });
    });
});
