/* global WPSP, jQuery */
jQuery(function ($) {

    var $generateBtn  = $('#wpsp-generate');
    var $pushBtn      = $('#wpsp-push-github');
    var $downloadBtn  = $('#wpsp-download-zip');
    var $testBtn      = $('#wpsp-test-github');
    var $statusText   = $('#wpsp-status-text');
    var $progress     = $('#wpsp-progress');
    var $progressBar  = $('#wpsp-progress-inner');
    var $result       = $('#wpsp-result');
    var $lastRun      = $('#wpsp-last-run');
    var $testResult   = $('#wpsp-github-test-result');

    // ── Fetch initial status ──────────────────────────────────────────────
    ajaxRequest('wpsp_get_status', {}, function (data) {
        if (data.has_site) {
            enableDeployButtons();
            var info = [];
            if (data.last_generated) info.push('Last generated: ' + data.last_generated);
            if (data.last_pushed)    info.push('Last pushed: ' + data.last_pushed);
            info.push(data.file_count + ' files in output');
            $lastRun.html(info.join(' &nbsp;·&nbsp; '));
        } else {
            $lastRun.html('<span class="wpsp-muted">No site generated yet.</span>');
        }
    });

    // ── Generate ──────────────────────────────────────────────────────────
    $generateBtn.on('click', function () {
        if (!confirm('This will crawl your entire site and overwrite the previous static output. Continue?')) return;

        setBusy(true, 'Crawling your site…');
        animateProgress(15, 85, 60000); // fake progress over ~60s

        ajaxRequest('wpsp_generate', {}, function (data) {
            stopProgress();
            setBusy(false);
            enableDeployButtons();

            var msg = '✅ ' + data.message;
            if (data.errors && data.errors.length) {
                msg += '<br><strong>Warnings:</strong> ' + data.errors.length + ' URL(s) had issues.';
            }
            showResult(msg, 'success');
            $lastRun.html('Last generated: just now &nbsp;·&nbsp; ' + data.pages + ' pages, ' + data.assets + ' assets');
        }, function (err) {
            stopProgress();
            setBusy(false);
            showResult('❌ Generation failed: ' + err, 'error');
        });
    });

    // ── Push to GitHub ─────────────────────────────────────────────────────
    $pushBtn.on('click', function () {
        if (!confirm('Push all static files to GitHub? This will overwrite existing files on the target branch.')) return;

        setBusy(true, 'Pushing to GitHub…');
        animateProgress(5, 95, 120000);

        ajaxRequest('wpsp_push_github', {}, function (data) {
            stopProgress();
            setBusy(false);

            var msg = '✅ ' + data.message;
            if (data.errors && data.errors.length) {
                msg += '<br><strong>Errors:</strong><br>' + data.errors.slice(0, 5).join('<br>');
            }
            if (data.pages_url) {
                msg += '<br><a href="' + data.pages_url + '" target="_blank">🌐 View GitHub Pages site →</a>';
            }
            showResult(msg, 'success');
        }, function (err) {
            stopProgress();
            setBusy(false);
            showResult('❌ Push failed: ' + err, 'error');
        });
    });

    // ── Download ZIP ───────────────────────────────────────────────────────
    $downloadBtn.on('click', function () {
        setBusy(true, 'Creating ZIP…');

        ajaxRequest('wpsp_download_zip', {}, function (data) {
            setBusy(false);
            showResult(
                '✅ ZIP ready (' + data.size + ') — <a href="' + data.download_url + '" download="' + data.filename + '">Click to download</a>',
                'success'
            );
            // Auto-trigger download
            var a = document.createElement('a');
            a.href = data.download_url;
            a.download = data.filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }, function (err) {
            setBusy(false);
            showResult('❌ ZIP failed: ' + err, 'error');
        });
    });

    // ── Test GitHub ────────────────────────────────────────────────────────
    $testBtn.on('click', function () {
        $testResult.text('Testing…');
        ajaxRequest('wpsp_test_github', {}, function (data) {
            $testResult.html('<span style="color:#1a6b3a">' + data.message + '</span>');
        }, function (err) {
            $testResult.html('<span style="color:#c0392b">❌ ' + err + '</span>');
        });
    });

    // ── Helpers ────────────────────────────────────────────────────────────
    function ajaxRequest(action, extraData, onSuccess, onError) {
        $.post(WPSP.ajax_url, $.extend({
            action: action,
            nonce:  WPSP.nonce,
        }, extraData), function (response) {
            if (response.success) {
                if (onSuccess) onSuccess(response.data);
            } else {
                var msg = (response.data && response.data.message) ? response.data.message : (response.data || 'Unknown error');
                if (onError) onError(msg);
            }
        }).fail(function () {
            if (onError) onError('Server request failed — check PHP error log.');
        });
    }

    function setBusy(busy, label) {
        $generateBtn.prop('disabled', busy);
        $pushBtn.prop('disabled', busy);
        $downloadBtn.prop('disabled', busy);
        if (busy) {
            $statusText.text(label || 'Working…');
            $progress.show();
        } else {
            $statusText.text('Done');
            $progress.hide();
        }
    }

    function enableDeployButtons() {
        $pushBtn.prop('disabled', false);
        $downloadBtn.prop('disabled', false);
    }

    function showResult(msg, type) {
        $result.removeClass('success error').addClass(type).html(msg).show();
    }

    var progressTimer = null;
    function animateProgress(from, to, duration) {
        var start = Date.now();
        $progressBar.css('width', from + '%');
        progressTimer = setInterval(function () {
            var elapsed  = Date.now() - start;
            var fraction = Math.min(elapsed / duration, 1);
            var val      = from + (to - from) * fraction;
            $progressBar.css('width', val + '%');
            if (fraction >= 1) clearInterval(progressTimer);
        }, 200);
    }

    function stopProgress() {
        if (progressTimer) clearInterval(progressTimer);
        $progressBar.css('width', '100%');
        setTimeout(function () { $progress.hide(); $progressBar.css('width', '0%'); }, 400);
    }

});
