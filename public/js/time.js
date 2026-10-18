// Epoch -> viewer-local time. All timestamps are stored UTC and emitted as
// epoch seconds in [data-epoch]; we render them in the browser's own timezone.
window.HR = window.HR || {};

HR.localizeEpochs = function (root) {
    root = root || document;
    root.querySelectorAll('[data-epoch]').forEach(function (el) {
        var ts = parseInt(el.getAttribute('data-epoch'), 10);
        if (!ts) {
            el.textContent = el.getAttribute('data-empty') || '—';
            return;
        }
        var fmt = el.getAttribute('data-format') || 'MMM D, YYYY · h:mm A';
        var d = dayjs.unix(ts);
        el.textContent = d.format(fmt);
        el.setAttribute('title', d.format('YYYY-MM-DD HH:mm:ss (Z)'));
    });
};

HR.tz = function () {
    try { return Intl.DateTimeFormat().resolvedOptions().timeZone; } catch (e) { return 'local'; }
};

document.addEventListener('DOMContentLoaded', function () {
    HR.localizeEpochs();
    document.querySelectorAll('[data-tz-label]').forEach(function (el) {
        el.textContent = HR.tz();
    });
});
