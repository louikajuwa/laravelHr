// JSON-API list: pagination + filters refresh the table without a full page reload.
// The server returns { data: [...], pagination: { current_page, last_page, per_page, total, from, to } }.
// Each page defines window.HR.pageRenderer = { cols: N, row: fn(item), empty: fn() }.
// The browser URL is never modified.
(function ($) {
    var HR = window.HR || (window.HR = {});

    HR.badge = function (color, text) {
        return '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold ' + color + '">' + text + '</span>';
    };

    HR.esc = function (str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };

    HR.epoch = function (ts, fmt) {
        if (!ts) return '—';
        return '<span data-epoch="' + ts + '" data-format="' + (fmt || 'MMM D, YYYY') + '">—</span>';
    };

    $(function () {
        var $container = $('[data-api-list]');
        if (!$container.length) return;

        var renderer = window.HR.pageRenderer;
        if (!renderer) return;

        var currentPage = 1;

        function load(page) {
            currentPage = page || 1;
            var url = $container.data('url');
            var $filter = $('[data-list-filter]');
            var params = $filter.length ? $filter.serializeArray() : [];
            params.push({ name: 'page', value: currentPage });

            $container.addClass('opacity-40 pointer-events-none');

            $.ajax({
                url: url,
                data: params,
                headers: { 'Accept': 'application/json' },
            }).done(function (res) {
                renderTable(res.data);
                renderPagination(res.pagination);
            }).fail(function () {
                $container.find('tbody').html(
                    '<tr><td colspan="' + (renderer.cols || 5) + '" class="px-5 py-10 text-center text-rose-500 text-sm">Failed to load data. Please refresh the page.</td></tr>'
                );
            }).always(function () {
                $container.removeClass('opacity-40 pointer-events-none');
            });
        }

        function renderTable(data) {
            var $tbody = $container.find('tbody');
            if (!data || !data.length) {
                var emptyHtml = renderer.empty
                    ? renderer.empty()
                    : '<tr><td colspan="' + (renderer.cols || 5) + '" class="px-5 py-10 text-center text-slate-400 text-sm">No records found.</td></tr>';
                $tbody.html(emptyHtml);
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

            var btnCls = 'px-3 py-1.5 rounded-xl text-xs font-semibold transition';
            var inactiveCls = btnCls + ' text-slate-500 hover:bg-slate-100';
            var activeCls   = btnCls + ' bg-accent-600 text-white pointer-events-none';
            var disabledCls = btnCls + ' text-slate-300 pointer-events-none';

            // Prev
            btns += cp > 1
                ? '<button data-page="' + (cp - 1) + '" class="' + inactiveCls + '">‹ Prev</button>'
                : '<span class="' + disabledCls + '">‹ Prev</span>';

            // Page numbers (window of 5)
            var start = Math.max(1, cp - 2);
            var end   = Math.min(lp, start + 4);
            start = Math.max(1, end - 4);

            if (start > 1) {
                btns += '<button data-page="1" class="' + inactiveCls + '">1</button>';
                if (start > 2) btns += '<span class="px-1 text-slate-400 text-xs self-center">…</span>';
            }
            for (var i = start; i <= end; i++) {
                btns += '<button data-page="' + i + '" class="' + (i === cp ? activeCls : inactiveCls) + '">' + i + '</button>';
            }
            if (end < lp) {
                if (end < lp - 1) btns += '<span class="px-1 text-slate-400 text-xs self-center">…</span>';
                btns += '<button data-page="' + lp + '" class="' + inactiveCls + '">' + lp + '</button>';
            }

            // Next
            btns += cp < lp
                ? '<button data-page="' + (cp + 1) + '" class="' + inactiveCls + '">Next ›</button>'
                : '<span class="' + disabledCls + '">Next ›</span>';

            $pag.html(
                '<div class="flex items-center gap-1">' + btns + '</div>' +
                '<p class="text-xs text-slate-400">Showing ' + meta.from + '–' + meta.to + ' of ' + meta.total + '</p>'
            );
        }

        // Pagination button clicks
        $container.on('click', '[data-pagination] [data-page]', function () {
            load(parseInt($(this).data('page'), 10));
        });

        // Filter form: submit + live change on selects / date inputs
        var $form = $('[data-list-filter]');
        if ($form.length) {
            $form.on('submit', function (e) { e.preventDefault(); load(1); });
            $form.on('change', 'select, input[type=date], input[type=month]', function () { load(1); });
            var t;
            $form.on('input', 'input[type=search], input[name=q]', function () {
                clearTimeout(t);
                t = setTimeout(function () { load(1); }, 350);
            });
        }

        // Initial load
        load(1);
    });
})(jQuery);
