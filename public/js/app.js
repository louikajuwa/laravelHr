(function ($) {
    var HR = window.HR || (window.HR = {});

    // CSRF + Accept JSON for all AJAX requests.
    var token = $('meta[name="csrf-token"]').attr('content');
    if (token) {
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': token } });
    }

    var FOCUSABLE = 'a[href], button:not([disabled]), input:not([type=hidden]):not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    // ---------------------------------------------------------------------------
    // Modal manager. Any [data-modal] element is a dialog.
    //   HR.openModal(elOrSelector, openerEl)  - shows, focuses first field, traps Tab
    //   HR.closeModal(elOrSelector)           - hides, returns focus to the opener
    // Esc and backdrop click close it. Buttons with [data-modal-close] close it.
    // ---------------------------------------------------------------------------
    function visibleModals() {
        return $('[data-modal]').filter(function () { return !$(this).hasClass('hidden'); });
    }

    HR.openModal = function (el, opener) {
        var $m = $(el).first();
        if (!$m.length) return;
        $m.data('opener', opener || document.activeElement);
        $m.attr({ role: 'dialog', 'aria-modal': 'true' }).removeClass('hidden').addClass('flex');
        var $first = $m.find('input:not([type=hidden]):not([disabled]), select:not([disabled]), textarea:not([disabled])').filter(':visible').first();
        if (!$first.length) $first = $m.find('button:not([disabled]), a[href]').filter(':visible').first();
        setTimeout(function () { if ($first.length) $first[0].focus(); }, 0);
    };

    HR.closeModal = function (el) {
        var $m = $(el).first();
        if (!$m.length || $m.hasClass('hidden')) return;
        $m.addClass('hidden').removeClass('flex');
        var opener = $m.data('opener');
        $m.removeData('opener');
        if (opener && document.body.contains(opener)) {
            try { opener.focus(); } catch (e) { /* ignore */ }
        }
    };

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
        $btn.attr('class', 'rounded-xl px-4 py-2 text-sm font-semibold text-white transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500 focus-visible:ring-offset-2 ' +
            (opts.confirmClass || 'bg-rose-600 hover:bg-rose-700'));

        // One-shot: closing first means a rapid double click cannot fire twice.
        $btn.off('click.confirm').on('click.confirm', function () {
            $btn.off('click.confirm');
            HR.closeModal($modal);
            if (opts.onConfirm) opts.onConfirm();
        });

        HR.openModal($modal, document.activeElement);
    };

    // Build + submit a hidden POST form once (guards against double clicks).
    HR.postForm = function (url, fields) {
        if (HR._posting) return;
        HR._posting = true;
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        form.style.display = 'none';
        var all = $.extend({ _token: $('meta[name=csrf-token]').attr('content') }, fields || {});
        Object.keys(all).forEach(function (k) {
            var i = document.createElement('input');
            i.type = 'hidden';
            i.name = k;
            i.value = all[k];
            form.appendChild(i);
        });
        document.body.appendChild(form);
        form.submit();
    };

    function lockForm($form) {
        $form.data('submitting', true).attr('aria-busy', 'true');
        $form.find('button[type=submit], button:not([type]), input[type=submit]').each(function () {
            $(this).prop('disabled', true).addClass('opacity-60 cursor-not-allowed');
        });
    }

    function unlockAll() {
        HR._posting = false;
        $('form').each(function () {
            var $f = $(this);
            if (!$f.data('submitting')) return;
            $f.removeData('submitting').removeData('confirmed').removeAttr('aria-busy');
            $f.find('button[type=submit], button:not([type]), input[type=submit]').prop('disabled', false).removeClass('opacity-60 cursor-not-allowed');
        });
    }
    // Back/forward cache restores the page with buttons still disabled.
    window.addEventListener('pageshow', function (e) { if (e.persisted) unlockAll(); });

    // Mobile sidebar.
    var lgQuery = window.matchMedia ? window.matchMedia('(min-width: 1024px)') : null;
    function setSidebar(open) {
        var $sb = $('[data-sidebar]');
        var desktop = lgQuery && lgQuery.matches;
        $sb.toggleClass('-translate-x-full', !open);
        // Off-canvas on small screens: hide from Tab order and screen readers.
        if (desktop || open) { $sb.removeAttr('inert'); } else { $sb.attr('inert', ''); }
        $('[data-sidebar-backdrop]').toggleClass('hidden', !open);
        $('[data-sidebar-toggle]').attr({
            'aria-expanded': open ? 'true' : 'false',
            'aria-label': open ? 'Close navigation menu' : 'Open navigation menu'
        });
        if (open && !desktop) { $sb.find('a, button').first().trigger('focus'); }
    }

    $(function () {
        // Auto-dismiss success flashes only. Errors (role=alert) stay until dismissed.
        setTimeout(function () {
            $('[data-flash-auto]').fadeOut(300);
        }, 4500);
        $(document).on('click', '[data-flash-close]', function () {
            $(this).closest('[data-flash]').fadeOut(200);
        });

        // Confirm destructive actions via styled modal instead of window.confirm().
        // Must be bound before the global double-submit guard below.
        // form[data-confirm-if="field"] only prompts while that checkbox/field is checked.
        $(document).on('submit', 'form[data-confirm]', function (e) {
            var $form = $(this);
            if ($form.data('confirmed')) return; // already confirmed: let it through
            var cond = $form.attr('data-confirm-if');
            if (cond && !$form.find('[name="' + cond + '"]:checked').length) return;
            e.preventDefault();
            if ($form.data('submitting')) return;
            HR.confirm({
                message: $form.data('confirm') || 'Are you sure?',
                confirmText: $form.data('confirm-text') || 'Confirm',
                confirmClass: $form.data('confirm-class') || undefined,
                onConfirm: function () {
                    $form.data('confirmed', true);
                    lockForm($form);
                    $form[0].submit(); // native submit: bypasses handlers, no re-prompt
                },
            });
        });

        // Global double-submit guard for every non-GET form.
        $(document).on('submit', 'form', function (e) {
            if (e.isDefaultPrevented()) return;
            var $form = $(this);
            var method = ($form.attr('method') || 'get').toLowerCase();
            if (method === 'get') return;
            if ($form.data('submitting')) { e.preventDefault(); return; }
            // Defer so the submitter's name/value is still part of the form data.
            setTimeout(function () { lockForm($form); }, 0);
        });

        // Sidebar toggle.
        setSidebar(false);
        if (lgQuery && lgQuery.addEventListener) {
            lgQuery.addEventListener('change', function () { setSidebar(false); });
        }
        $(document).on('click', '[data-sidebar-toggle]', function () {
            setSidebar($('[data-sidebar]').hasClass('-translate-x-full'));
        });
        $(document).on('click', '[data-sidebar-backdrop]', function () {
            setSidebar(false);
            $('[data-sidebar-toggle]').first().trigger('focus');
        });

        // Modals.
        $(document).on('click', '[data-modal-open]', function () {
            HR.openModal('#' + $(this).data('modal-open'), this);
        });
        $(document).on('click', '[data-modal-close]', function () {
            HR.closeModal($(this).closest('[data-modal]'));
        });
        $(document).on('click', '[data-modal]', function (e) {
            if (e.target === this) HR.closeModal(this); // backdrop click
        });
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape') {
                var $open = visibleModals().last();
                if ($open.length) { HR.closeModal($open); return; }
                if ($('[data-sidebar]').length && !$('[data-sidebar]').hasClass('-translate-x-full') && !(lgQuery && lgQuery.matches)) {
                    setSidebar(false);
                    $('[data-sidebar-toggle]').first().trigger('focus');
                }
                return;
            }
            // Focus trap inside the open modal.
            if (e.key === 'Tab') {
                var $m = visibleModals().last();
                if (!$m.length) return;
                var $f = $m.find(FOCUSABLE).filter(':visible');
                if (!$f.length) return;
                var first = $f[0], last = $f[$f.length - 1];
                if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
                else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
                else if (!$m[0].contains(document.activeElement)) { e.preventDefault(); first.focus(); }
            }
        });

        // Re-open a modal on load when the server flagged it (validation errors).
        $('[data-modal][data-reopen="1"]').each(function () { HR.openModal(this, null); });

        // Global handler: [data-delete-url] buttons rendered by JS page renderers.
        // Sends a DELETE form POST after confirmation.
        $(document).on('click', '[data-delete-url]', function () {
            var url = $(this).data('delete-url');
            var msg = $(this).data('delete-msg') || 'Delete this record?';
            HR.confirm({
                message: msg,
                confirmText: 'Delete',
                confirmClass: 'bg-rose-600 hover:bg-rose-700',
                onConfirm: function () { HR.postForm(url, { _method: 'DELETE' }); },
            });
        });

        // Global handler: [data-review-url] for late arrival Accept/Excuse/Reject buttons.
        $(document).on('click', '[data-review-url]', function () {
            var url = $(this).data('review-url');
            var status = String($(this).data('review-status'));
            var msg = $(this).data('review-msg') || 'Set status to ' + status + '?';
            var cls = status === 'accepted' ? 'bg-emerald-600 hover:bg-emerald-700'
                    : status === 'excused'  ? 'bg-sky-600 hover:bg-sky-700'
                    : 'bg-rose-600 hover:bg-rose-700';
            HR.confirm({
                message: msg,
                confirmText: status.charAt(0).toUpperCase() + status.slice(1),
                confirmClass: cls,
                onConfirm: function () { HR.postForm(url, { status: status }); },
            });
        });
    });
})(jQuery);
