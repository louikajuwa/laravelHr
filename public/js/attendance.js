(function ($) {
    $(function () {
        var $card = $('#clock-card');
        if (!$card.length) return;

        // Live local clock.
        var $clock = $('#live-clock');
        function tick() { if ($clock.length) $clock.text(dayjs().format('h:mm:ss A')); }
        tick();
        setInterval(tick, 1000);

        function post(action, $btn) {
            $btn.prop('disabled', true).addClass('opacity-60');
            $.post($card.data(action + '-url'))
                .done(function (res) {
                    HR.toast(res.message, 'success');
                    setTimeout(function () { window.location.reload(); }, 700);
                })
                .fail(function (xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong.';
                    HR.toast(msg, 'error');
                    $btn.prop('disabled', false).removeClass('opacity-60');
                });
        }

        $('#clock-in-btn').on('click', function () { post('clockin', $(this)); });
        $('#clock-out-btn').on('click', function () { post('clockout', $(this)); });
    });

    // Lightweight toast.
    HR.toast = function (message, type) {
        var color = type === 'error' ? 'bg-rose-600' : 'bg-emerald-600';
        var isError = type === 'error';
        var $t = $('<div class="fixed bottom-6 right-6 z-50 ' + color + ' text-white px-4 py-3 rounded-xl shadow-lg text-sm"></div>')
            .attr('role', isError ? 'alert' : 'status')
            .text(message).appendTo('body');
        function dismiss() { $t.fadeOut(300, function () { $t.remove(); }); }
        if (isError) {
            // Errors persist until dismissed (click).
            $t.attr('title', 'Click to dismiss').css('cursor', 'pointer').on('click', dismiss);
        } else {
            setTimeout(dismiss, 3000);
        }
    };
})(jQuery);
