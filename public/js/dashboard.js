(function ($) {
    $(function () {
        var $root = $('#hr-dashboard');
        if (!$root.length || typeof Chart === 'undefined') return;

        Chart.defaults.font.family = 'Inter, ui-sans-serif, system-ui, sans-serif';
        Chart.defaults.color = '#64748b';

        $.getJSON($root.data('metrics-url')).done(function (m) {
            // Today donut
            var donut = document.getElementById('chart-today');
            if (donut) {
                new Chart(donut, {
                    type: 'doughnut',
                    data: {
                        labels: ['Present', 'Late', 'On Leave', 'Absent'],
                        datasets: [{
                            data: [m.today.present, m.today.late, m.today.on_leave, m.today.absent],
                            backgroundColor: ['#10b981', '#f59e0b', '#0ea5e9', '#f43f5e'],
                            borderWidth: 0,
                        }],
                    },
                    options: { cutout: '68%', plugins: { legend: { position: 'bottom' } } },
                });
            }

            // 30-day trend line
            var line = document.getElementById('chart-trend');
            if (line) {
                new Chart(line, {
                    type: 'line',
                    data: {
                        labels: m.trend.labels,
                        datasets: [
                            { label: 'Present', data: m.trend.present, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.1)', tension: .35, fill: true },
                            { label: 'Late', data: m.trend.late, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.1)', tension: .35, fill: true },
                            { label: 'Absent', data: m.trend.absent, borderColor: '#f43f5e', backgroundColor: 'rgba(244,63,94,.1)', tension: .35, fill: true },
                        ],
                    },
                    options: {
                        plugins: { legend: { position: 'bottom' } },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                    },
                });
            }

            // Leave by type bar
            var bar = document.getElementById('chart-leave');
            if (bar) {
                new Chart(bar, {
                    type: 'bar',
                    data: {
                        labels: m.leave_by_type.map(function (r) { return r.name; }),
                        datasets: [{ label: 'Approved', data: m.leave_by_type.map(function (r) { return r.total; }), backgroundColor: '#6366f1', borderRadius: 6 }],
                    },
                    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
                });
            }
        }).fail(function () {
            $root.find('canvas').each(function () {
                $(this).replaceWith('<p role="alert" class="py-10 text-center text-sm text-rose-600">Could not load chart data. Please refresh the page.</p>');
            });
        });
    });
})(jQuery);
