(function ($) {
    var HR = window.HR || (window.HR = {});

    // CSRF + Accept JSON for all AJAX requests.
    var token = $('meta[name="csrf-token"]').attr('content');
    if (token) {
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': token } });
    }

    // ---------------------------------------------------------------------------
    // Confirmation modal API.
    // Usage: HR.confirm({ title, message, confirmText, confirmClass, onConfirm })
    // ---------------------------------------------------------------------------
    HR.confirm = function (opts) {
        var $modal = $('#hr-confirm-modal');
        $modal.find('#hr-confirm-title').text(opts.title || 'Confirm action');
        $modal.find('#hr-confirm-message').text(opts.message || 'Are you sure you want to proceed?');

        var $btn = $modal.find('#hr-confirm-ok');
        $btn.text(opts.confirmText || 'Confirm');
        $btn.attr('class', 'rounded-xl px-4 py-2 text-sm font-semibold text-white transition ' +
            (opts.confirmClass || 'bg-rose-600 hover:bg-rose-700'));

        $btn.off('click.confirm').on('click.confirm', function () {
            $modal.addClass('hidden').removeClass('flex');
            if (opts.onConfirm) opts.onConfirm();
        });

        $modal.removeClass('hidden').addClass('flex');
    };

    $(function () {
        // Auto-dismiss flash messages.
        setTimeout(function () {
            $('[data-flash]').fadeOut(300);
        }, 4500);
        $(document).on('click', '[data-flash-close]', function () {
            $(this).closest('[data-flash]').fadeOut(200);
        });

        // Confirm destructive actions via styled modal instead of window.confirm().
        $(document).on('submit', 'form[data-confirm]', function (e) {
            e.preventDefault();
            var $form = $(this);
            var message = $form.data('confirm') || 'Are you sure?';
            HR.confirm({
                message: message,
                onConfirm: function () {
                    $form.off('submit.confirm').submit();
                },
            });
        });

        // Mobile sidebar toggle.
        $(document).on('click', '[data-sidebar-toggle]', function () {
            $('[data-sidebar]').toggleClass('-translate-x-full');
            $('[data-sidebar-backdrop]').toggleClass('hidden');
        });
        $(document).on('click', '[data-sidebar-backdrop]', function () {
            $('[data-sidebar]').addClass('-translate-x-full');
            $(this).addClass('hidden');
        });

        // Simple modal open/close via [data-modal-open="id"] / [data-modal-close].
        $(document).on('click', '[data-modal-open]', function () {
            $('#' + $(this).data('modal-open')).removeClass('hidden').addClass('flex');
        });
        $(document).on('click', '[data-modal-close]', function () {
            $(this).closest('[data-modal]').addClass('hidden').removeClass('flex');
        });

        // Close confirmation modal on backdrop click.
        $(document).on('click', '#hr-confirm-modal', function (e) {
            if (e.target === this) {
                $(this).addClass('hidden').removeClass('flex');
            }
        });

        // Global handler: [data-delete-url] buttons rendered by JS page renderers.
        // Sends a DELETE form POST after confirmation.
        $(document).on('click', '[data-delete-url]', function () {
            var url = $(this).data('delete-url');
            var msg = $(this).data('delete-msg') || 'Delete this record?';
            HR.confirm({
                message: msg,
                confirmText: 'Delete',
                confirmClass: 'bg-rose-600 hover:bg-rose-700',
                onConfirm: function () {
                    var $form = $('<form method="POST" action="' + url + '" style="display:none">');
                    $form.append('<input type="hidden" name="_token" value="' + $('meta[name=csrf-token]').attr('content') + '">');
                    $form.append('<input type="hidden" name="_method" value="DELETE">');
                    $('body').append($form);
                    $form.submit();
                },
            });
        });

        // Global handler: [data-review-url] for late arrival Accept/Excuse/Reject buttons.
        $(document).on('click', '[data-review-url]', function () {
            var url = $(this).data('review-url');
            var status = $(this).data('review-status');
            var msg = $(this).data('review-msg') || 'Set status to ' + status + '?';
            var cls = status === 'accepted' ? 'bg-emerald-600 hover:bg-emerald-700'
                    : status === 'excused'  ? 'bg-sky-600 hover:bg-sky-700'
                    : 'bg-rose-600 hover:bg-rose-700';
            HR.confirm({
                message: msg,
                confirmText: status.charAt(0).toUpperCase() + status.slice(1),
                confirmClass: cls,
                onConfirm: function () {
                    var $form = $('<form method="POST" action="' + url + '" style="display:none">');
                    $form.append('<input type="hidden" name="_token" value="' + $('meta[name=csrf-token]').attr('content') + '">');
                    $form.append('<input type="hidden" name="status" value="' + status + '">');
                    $('body').append($form);
                    $form.submit();
                },
            });
        });
    });
})(jQuery);
