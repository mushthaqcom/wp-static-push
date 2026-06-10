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
    var $logPanel     = $('#wpsp-log-panel');
    var $logSummary   = $('#wpsp-log-summary');
    var $logEntries   = $('#wpsp-log-entries');
    var $lastRun      = $('#wpsp-last-run');
    var $testResult   = $('#wpsp-github-test-result');

    var generateMessages = [
        'Crawling your site…',
        'Following internal links…',
        'Downloading assets (CSS, JS, images)…',
        'Processing page content…',
        'Building static HTML files…',
        'Generating SEO files…',
        'Finalising output…',
    ];

    var pushMessages = [
        'Reading output files…',
        'Creating file blobs on GitHub…',
        'Still uploading blobs…',
        'Building git tree…',
        'Creating single commit…',
        'Updating branch ref…',
        'Almost done…',
    ];

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

        clearLog();
        setBusy(true, generateMessages[0]);
        animateProgress(5, 90, 90000);
        cycleMessages(generateMessages, 8000);

        ajaxRequest('wpsp_generate', {}, function (data) {
            stopProgress();
            stopCycling();
            setBusy(false);
            enableDeployButtons();

            var msg = '<strong>Generated ' + data.pages + ' pages &amp; ' + data.assets + ' assets</strong> in ' + data.duration + 's';
            if (data.seo_files && data.seo_files.length) {
                msg += '<br><span class="wpsp-log-meta">SEO files: ' + data.seo_files.join(', ') + '</span>';
            }
            if (data.errors && data.errors.length) {
                msg += '<br><span class="wpsp-log-warn">' + data.errors.length + ' URL(s) had issues — see log below</span>';
            }
            showResult(msg, 'success');

            var logLines = (data.log || []).slice();
            if (data.errors && data.errors.length) {
                logLines = logLines.concat(data.errors);
            }
            showLog('Generate log (' + logLines.length + ' entries)', logLines);

            $lastRun.html('Last generated: just now &nbsp;·&nbsp; ' + data.pages + ' pages, ' + data.assets + ' assets');
        }, function (err) {
            stopProgress();
            stopCycling();
            setBusy(false);
            showResult('<strong>Generation failed</strong><br>' + err, 'error');
        });
    });

    // ── Push to GitHub ─────────────────────────────────────────────────────
    $pushBtn.on('click', function () {
        if (!confirm('Push all static files to GitHub as a single commit? This will overwrite the target branch content.')) return;

        clearLog();
        setBusy(true, pushMessages[0]);
        animateProgress(5, 90, 120000);
        cycleMessages(pushMessages, 10000);

        ajaxRequest('wpsp_push_github', {}, function (data) {
            stopProgress();
            stopCycling();
            setBusy(false);

            var branch = data.log && data.log.length ? (data.log.filter(function(l){ return l.indexOf('branch "') !== -1; })[0] || '').replace(/.*branch "([^"]+)".*/, '$1') : '';
            var msg = '<strong>Pushed ' + data.pushed + ' files</strong> in a single commit — ' + data.duration + 's';
            if (branch) msg += '<br><span class="wpsp-log-meta">Branch: ' + escHtml(branch) + '</span>';
            if (data.commit_url) {
                msg += '<br><a href="' + escHtml(data.commit_url) + '" target="_blank" rel="noopener">View commit ' + escHtml(data.commit_sha.slice(0, 7)) + ' on GitHub →</a>';
            }
            if (data.pages_url) {
                msg += '<br><a href="' + escHtml(data.pages_url) + '" target="_blank" rel="noopener">View GitHub Pages site →</a>';
            }
            if (data.errors && data.errors.length) {
                msg += '<br><span class="wpsp-log-warn">' + data.errors.length + ' file(s) failed — see log</span>';
            }
            showResult(msg, 'success');

            var logLines = (data.log || []).slice();
            if (data.errors && data.errors.length) {
                logLines = logLines.concat(data.errors);
            }
            showLog('Push log (' + logLines.length + ' steps)', logLines);
        }, function (err) {
            stopProgress();
            stopCycling();
            setBusy(false);
            showResult('<strong>Push failed</strong><br>' + err, 'error');
        });
    });

    // ── Download ZIP ───────────────────────────────────────────────────────
    $downloadBtn.on('click', function () {
        setBusy(true, 'Creating ZIP archive…');

        ajaxRequest('wpsp_download_zip', {}, function (data) {
            setBusy(false);
            showResult(
                'ZIP ready (' + data.size + ') — <a href="' + data.download_url + '" download="' + data.filename + '">Click to download</a>',
                'success'
            );
            var a = document.createElement('a');
            a.href = data.download_url;
            a.download = data.filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }, function (err) {
            setBusy(false);
            showResult('<strong>ZIP failed</strong><br>' + err, 'error');
        });
    });

    // ── Test GitHub ────────────────────────────────────────────────────────
    $testBtn.on('click', function () {
        $testResult.text('Testing…');
        ajaxRequest('wpsp_test_github', {}, function (data) {
            $testResult.html('<span style="color:#1a6b3a">' + escHtml(data.message) + '</span>');
        }, function (err) {
            $testResult.html('<span style="color:#c0392b">&#10060; ' + escHtml(err) + '</span>');
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
        }).fail(function (xhr) {
            var msg = 'Server request failed (HTTP ' + xhr.status + ').';
            if (xhr.status === 0) msg = 'Server request failed — possible timeout or network error.';
            if (onError) onError(msg);
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

    function showLog(summaryText, lines) {
        if (!lines || !lines.length) { $logPanel.hide(); return; }
        $logSummary.text(summaryText);
        var html = '';
        lines.forEach(function (line) {
            var cls = 'wpsp-log-line';
            if (line.indexOf('[ERROR]') !== -1) cls += ' wpsp-log-error';
            else if (line.indexOf('[WARN]') !== -1 || line.indexOf('Warning') !== -1 || line.indexOf('HTTP 4') !== -1) cls += ' wpsp-log-warning';
            html += '<div class="' + cls + '">' + escHtml(line) + '</div>';
        });
        $logEntries.html(html);
        $logPanel.show();
    }

    function clearLog() {
        $logPanel.hide();
        $logEntries.empty();
        $result.hide();
    }

    function escHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
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
        }, 300);
    }

    function stopProgress() {
        if (progressTimer) clearInterval(progressTimer);
        $progressBar.css('width', '100%');
        setTimeout(function () { $progress.hide(); $progressBar.css('width', '0%'); }, 400);
    }

    var cycleTimer = null;
    function cycleMessages(messages, interval) {
        var idx = 1;
        cycleTimer = setInterval(function () {
            if (idx < messages.length) {
                $statusText.text(messages[idx]);
                idx++;
            }
        }, interval);
    }

    function stopCycling() {
        if (cycleTimer) { clearInterval(cycleTimer); cycleTimer = null; }
    }

});
