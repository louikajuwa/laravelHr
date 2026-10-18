// JSON-API list: pagination + filters refresh the table without a full page reload.
// The server returns { data: [...], pagination: { current_page, last_page, per_page, total, from, to } }.
// Each page defines window.HR.pageRenderer = { cols: N, row: fn(item), empty: fn() }.
// Page + filters are mirrored into the URL query (history.replaceState) and restored on load.
(function ($) {
    var HR = window.HR || (window.HR = {});

    HR.esc = function (str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    // `text` is plain text and is escaped here; do not pre-escape it.
    HR.badge = function (color, text) {
        return '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ' + HR.esc(color) + '">' + HR.esc(text) + '</span>';
    };

    HR.epoch = function (ts, fmt) {
        if (!ts) return '—';
        return '<span data-epoch="' + HR.esc(ts) + '" data-format="' + HR.esc(fmt || 'MMM D, YYYY') + '">—</span>';
    };

    // Empty-state row (icon + message), mirrors components/empty.blade.php.
    HR.emptyRow = function (cols, message, tone) {
        var iconCls = tone === 'error' ? 'bg-rose-50 text-rose-500' : 'bg-slate-100 text-slate-400';
        var path = tone === 'error'
            ? 'M12 9v4m0 4h.01M10.3 3.86l-8.1 14a1 1 0 00.86 1.5h16.2a1 1 0 00.86-1.5l-8.1-14a1 1 0 00-1.72 0z'
            : 'M21 21l-4.3-4.3M11 19a8 8 0 100-16 8 8 0 000 16z';
        return '<tr><td colspan="' + (cols || 5) + '" class="px-6 py-12">' +
            '<div class="flex flex-col items-center justify-center gap-2 text-center"' + (tone === 'error' ? ' role="alert"' : '') + '>' +
            '<span class="flex h-12 w-12 items-center justify-center rounded-2xl ' + iconCls + '">' +
            '<svg aria-hidden="true" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="' + path + '"/></svg></span>' +
            '<p class="text-sm ' + (tone === 'error' ? 'text-rose-600' : 'text-slate-500') + '">' + HR.esc(message) + '</p>' +
            '</div></td></tr>';
    };

    $(function () {
        var $container = $('[data-api-list]');
        if (!$container.length) return;

        var renderer = window.HR.pageRenderer;
        if (!renderer) return;

        var $form = $('[data-list-filter]');
        var currentPage = 1;
        var inflight = null;
        var seq = 0;

        // ---- Restore state from the URL query -------------------------------
        (function restore() {
            var qs = new URLSearchParams(window.location.search);
            var p = parseInt(qs.get('page'), 10);
            if (p > 0) currentPage = p;
            $form.find('[name]').each(function () {
                var name = this.getAttribute('name');
                if (qs.has(name) && (this.tagName === 'SELECT' || this.type !== 'checkbox')) {
                    $(this).val(qs.get(name));
                }
            });
        })();

        function syncUrl(params) {
            if (!window.history || !history.replaceState) return;
            var qs = new URLSearchParams();
            params.forEach(function (p) {
                if (p.value === '' || p.value == null) return;
                if (p.name === 'page' && String(p.value) === '1') return;
                qs.append(p.name, p.value);
            });
            var s = qs.toString();
            history.replaceState(null, '', window.location.pathname + (s ? '?' + s : '') + window.location.hash);
        }

        function load(page) {
            currentPage = page || 1;
            var url = $container.data('url');
            var params = $form.length ? $form.serializeArray() : [];
            params.push({ name: 'page', value: currentPage });
            syncUrl(params);

            // Cancel the previous request; its response must never overwrite a newer one.
            if (inflight) inflight.abort();
            var mySeq = ++seq;

            $container.addClass('opacity-40 pointer-events-none').attr('aria-busy', 'true');
            $container.find('table').attr('aria-busy', 'true');

            inflight = $.ajax({
                url: url,
                data: params,
                headers: { 'Accept': 'application/json' },
            }).done(function (res) {
                if (mySeq !== seq) return;
                renderTable(res.data);
                renderPagination(res.pagination);
            }).fail(function (xhr, status) {
                if (status === 'abort' || mySeq !== seq) return;
                $container.find('tbody').html(HR.emptyRow(renderer.cols, 'Failed to load data. Please refresh the page.', 'error'));
                $container.find('[data-pagination]').addClass('hidden').html('');
            }).always(function () {
                if (mySeq !== seq) return;
                inflight = null;
                $container.removeClass('opacity-40 pointer-events-none').removeAttr('aria-busy');
                $container.find('table').removeAttr('aria-busy');
            });
        }

        function renderTable(data) {
            var $tbody = $container.find('tbody');
            if (!data || !data.length) {
                $tbody.html(renderer.empty ? renderer.empty() : HR.emptyRow(renderer.cols, 'No records found.'));
                return;
            }
            $tbody.html(data.map(renderer.row).join(''));
            if (HR.localizeEpochs) HR.localizeEpochs($container[0]);
        }

        function renderPagination(meta) {
            var $pag = $container.find('[data-pagination]');
            if (!$pag.length) return;

            if (!meta || meta.last_page <= 1) {
                $pag.addClass('hidden').html('');
                return;
            }
            $pag.removeClass('hidden');

            var btns = '';
            var cp = meta.current_page;
            var lp = meta.last_page;

            var btnCls = 'px-3 py-1.5 rounded-xl text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-500';
            var inactiveCls = btnCls + ' text-slate-600 hover:bg-slate-100';
            var activeCls   = btnCls + ' bg-accent-600 text-white pointer-events-none';
            var disabledCls = 'px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-400 pointer-events-none';

            // Prev
            btns += cp > 1
                ? '<button type="button" data-page="' + (cp - 1) + '" aria-label="Previous page" class="' + inactiveCls + '">‹ Prev</button>'
                : '<span aria-disabled="true" class="' + disabledCls + '">‹ Prev</span>';

            // Page numbers (window of 5)
            var start = Math.max(1, cp - 2);
            var end   = Math.min(lp, start + 4);
            start = Math.max(1, end - 4);

            if (start > 1) {
                btns += '<button type="button" data-page="1" aria-label="Page 1" class="' + inactiveCls + '">1</button>';
                if (start > 2) btns += '<span aria-hidden="true" class="px-1 text-slate-500 text-xs self-center">…</span>';
            }
            for (var i = start; i <= end; i++) {
                btns += '<button type="button" data-page="' + i + '" aria-label="Page ' + i + '"' +
                    (i === cp ? ' aria-current="page"' : '') +
                    ' class="' + (i === cp ? activeCls : inactiveCls) + '">' + i + '</button>';
            }
            if (end < lp) {
                if (end < lp - 1) btns += '<span aria-hidden="true" class="px-1 text-slate-500 text-xs self-center">…</span>';
                btns += '<button type="button" data-page="' + lp + '" aria-label="Page ' + lp + '" class="' + inactiveCls + '">' + lp + '</button>';
            }

            // Next
            btns += cp < lp
                ? '<button type="button" data-page="' + (cp + 1) + '" aria-label="Next page" class="' + inactiveCls + '">Next ›</button>'
                : '<span aria-disabled="true" class="' + disabledCls + '">Next ›</span>';

            $pag.html(
                '<nav aria-label="Pagination" class="flex items-center gap-1">' + btns + '</nav>' +
                '<p class="text-xs text-slate-500">Showing ' + HR.esc(meta.from) + '–' + HR.esc(meta.to) + ' of ' + HR.esc(meta.total) + '</p>'
            );
        }

        // Pagination button clicks
        $container.on('click', '[data-pagination] [data-page]', function () {
            load(parseInt($(this).data('page'), 10));
        });

        // Filter form: submit + live change on selects / date inputs
        if ($form.length) {
            $form.on('submit', function (e) { e.preventDefault(); load(1); });
            $form.on('change', 'select, input[type=date], input[type=month]', function () { load(1); });
            var t;
            $form.on('input', 'input[type=search], input[name=q]', function () {
                clearTimeout(t);
                t = setTimeout(function () { load(1); }, 350);
            });
        }

        // Initial load (restored page, if any)
        load(currentPage);
    });
})(jQuery);
