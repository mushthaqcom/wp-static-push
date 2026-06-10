<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wpsp-wrap">

<div class="wpsp-header">
    <div class="wpsp-header-inner">
        <div class="wpsp-logo">
            <span class="dashicons dashicons-cloud-upload"></span>
            <h1>WP Static Push</h1>
        </div>
        <span class="wpsp-version">v<?php echo WPSP_VERSION; ?></span>
    </div>
</div>

<?php if ( isset( $_GET['saved'] ) ): ?>
<div class="notice notice-success is-dismissible"><p>✅ Settings saved.</p></div>
<?php endif; ?>

<div class="wpsp-grid">

    <!-- ===================== LEFT COLUMN ===================== -->
    <div class="wpsp-col-main">

        <!-- GENERATE + DEPLOY CARD -->
        <div class="wpsp-card wpsp-card-primary">
            <h2>🚀 Generate & Deploy</h2>
            <p class="wpsp-muted">Crawl your site, build static HTML, then download or push to GitHub.</p>

            <div class="wpsp-status-bar" id="wpsp-status-bar">
                <span id="wpsp-status-text">Ready</span>
                <div class="wpsp-progress" id="wpsp-progress" style="display:none;">
                    <div class="wpsp-progress-inner" id="wpsp-progress-inner"></div>
                </div>
            </div>

            <div class="wpsp-actions">
                <button class="wpsp-btn wpsp-btn-primary" id="wpsp-generate">
                    <span class="dashicons dashicons-update"></span> Generate Static Site
                </button>
                <button class="wpsp-btn wpsp-btn-github" id="wpsp-push-github" disabled>
                    <span class="dashicons dashicons-cloud-upload"></span> Push to GitHub
                </button>
                <button class="wpsp-btn wpsp-btn-secondary" id="wpsp-download-zip" disabled>
                    <span class="dashicons dashicons-download"></span> Download ZIP
                </button>
            </div>

            <div class="wpsp-result" id="wpsp-result" style="display:none;"></div>

            <details class="wpsp-log-panel" id="wpsp-log-panel" style="display:none;">
                <summary id="wpsp-log-summary">View detailed log</summary>
                <div class="wpsp-log-entries" id="wpsp-log-entries"></div>
            </details>

            <div class="wpsp-last-run" id="wpsp-last-run">
                <span class="wpsp-muted">Checking status…</span>
            </div>
        </div>

        <!-- SETTINGS CARD -->
        <div class="wpsp-card">
            <h2>⚙️ Settings</h2>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('wpsp_save_settings'); ?>
                <input type="hidden" name="action" value="wpsp_save_settings">

                <h3 class="wpsp-section-title">🐙 GitHub</h3>
                <div class="wpsp-fields">
                    <div class="wpsp-field">
                        <label>Personal Access Token
                            <a href="https://github.com/settings/tokens/new?scopes=repo&description=WP+Static+Push" target="_blank" class="wpsp-link">Generate →</a>
                        </label>
                        <input type="password" name="github_token"
                            value="<?php echo esc_attr($settings['github_token']); ?>"
                            placeholder="ghp_xxxxxxxxxxxx" autocomplete="off">
                        <small>Needs <code>repo</code> scope (or <code>public_repo</code> for public repos)</small>
                    </div>
                    <div class="wpsp-field">
                        <label>Repository <small>(owner/repo)</small></label>
                        <input type="text" name="github_repo"
                            value="<?php echo esc_attr($settings['github_repo']); ?>"
                            placeholder="yourname/your-site">
                    </div>
                    <div class="wpsp-fields-row">
                        <div class="wpsp-field">
                            <label>Branch</label>
                            <input type="text" name="github_branch"
                                value="<?php echo esc_attr($settings['github_branch'] ?: 'gh-pages'); ?>"
                                placeholder="gh-pages">
                        </div>
                        <div class="wpsp-field">
                            <label>Sub-directory <small>(optional)</small></label>
                            <input type="text" name="github_subdir"
                                value="<?php echo esc_attr($settings['github_subdir']); ?>"
                                placeholder="docs">
                        </div>
                    </div>
                    <div class="wpsp-inline">
                        <button type="button" class="wpsp-btn wpsp-btn-sm" id="wpsp-test-github">
                            Test Connection
                        </button>
                        <span id="wpsp-github-test-result"></span>
                    </div>
                </div>

                <h3 class="wpsp-section-title">🌐 Site</h3>
                <div class="wpsp-fields">
                    <div class="wpsp-field">
                        <label>Production Base URL <small>(optional — defaults to WordPress site URL)</small></label>
                        <input type="url" name="base_url"
                            value="<?php echo esc_attr($settings['base_url']); ?>"
                            placeholder="<?php echo get_site_url(); ?>">
                        <small>Use this if your static site will be hosted at a different URL (e.g. GitHub Pages URL)</small>
                    </div>
                    <div class="wpsp-field">
                        <label>Exclude Paths <small>(one per line)</small></label>
                        <textarea name="exclude_paths" rows="4"
                            placeholder="/cart&#10;/checkout&#10;/my-account"><?php echo esc_textarea($settings['exclude_paths']); ?></textarea>
                    </div>
                    <div class="wpsp-field">
                        <label>Crawl Depth</label>
                        <input type="number" name="crawl_depth" min="1" max="20"
                            value="<?php echo intval($settings['crawl_depth'] ?: 5); ?>">
                        <small>How many levels deep to follow links (5 is usually enough)</small>
                    </div>
                </div>

                <h3 class="wpsp-section-title">🔍 SEO Files</h3>
                <div class="wpsp-fields">
                    <div class="wpsp-checks">
                        <label class="wpsp-check">
                            <input type="checkbox" name="generate_sitemap" value="1"
                                <?php checked($settings['generate_sitemap'], '1'); ?>>
                            <span>Generate <code>sitemap.xml</code></span>
                        </label>
                        <label class="wpsp-check">
                            <input type="checkbox" name="generate_robots" value="1"
                                <?php checked($settings['generate_robots'], '1'); ?>>
                            <span>Generate <code>robots.txt</code></span>
                        </label>
                        <label class="wpsp-check">
                            <input type="checkbox" name="generate_404" value="1"
                                <?php checked($settings['generate_404'], '1'); ?>>
                            <span>Generate <code>404.html</code></span>
                        </label>
                    </div>
                </div>

                <div class="wpsp-form-footer">
                    <?php submit_button( 'Save Settings', 'primary', 'submit', false ); ?>
                </div>
            </form>
        </div>

    </div><!-- /col-main -->

    <!-- ===================== RIGHT COLUMN ===================== -->
    <div class="wpsp-col-side">

        <!-- SITE INFO CARD -->
        <div class="wpsp-card">
            <h2>🔍 Site Analysis</h2>
            <p class="wpsp-muted">How your current setup will affect static generation.</p>

            <div class="wpsp-site-meta">
                <div class="wpsp-meta-row">
                    <span>WordPress</span>
                    <strong>v<?php echo esc_html($site_info['wp_version']); ?></strong>
                </div>
                <div class="wpsp-meta-row">
                    <span>Posts</span>
                    <strong><?php echo intval($site_info['total_posts']); ?></strong>
                </div>
                <div class="wpsp-meta-row">
                    <span>Pages</span>
                    <strong><?php echo intval($site_info['total_pages']); ?></strong>
                </div>
            </div>

            <h3 class="wpsp-section-title">Active Theme</h3>
            <div class="wpsp-theme-row">
                <div class="wpsp-theme-info">
                    <strong><?php echo esc_html($site_info['theme']['name']); ?></strong>
                    <small>v<?php echo esc_html($site_info['theme']['version']); ?></small>
                </div>
                <span class="wpsp-compat-note"><?php echo esc_html($site_info['theme']['note']); ?></span>
            </div>

            <h3 class="wpsp-section-title">Active Plugins (<?php echo count($site_info['plugins']); ?>)</h3>
            <div class="wpsp-plugin-list">
                <?php if ( empty($site_info['plugins']) ): ?>
                    <p class="wpsp-muted">No active plugins.</p>
                <?php else: ?>
                    <?php foreach ( $site_info['plugins'] as $plugin ): ?>
                    <div class="wpsp-plugin-row">
                        <div class="wpsp-plugin-info">
                            <strong><?php echo esc_html($plugin['name']); ?></strong>
                            <small>v<?php echo esc_html($plugin['version']); ?></small>
                        </div>
                        <span class="wpsp-compat-note small"><?php echo esc_html($plugin['note']); ?></span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- HELP CARD -->
        <div class="wpsp-card wpsp-card-info">
            <h2>📖 Quick Guide</h2>
            <ol class="wpsp-guide">
                <li>Set your GitHub token + repo, save settings.</li>
                <li>Optionally set a Production URL if your static site will be hosted elsewhere.</li>
                <li>Click <strong>Generate Static Site</strong> — this crawls every page.</li>
                <li>Then either <strong>Push to GitHub</strong> or <strong>Download ZIP</strong>.</li>
            </ol>

            <h3 class="wpsp-section-title">⚠️ Limitations</h3>
            <ul class="wpsp-guide">
                <li>Dynamic forms (Contact Form 7, WooCommerce checkout) won't function in static output — use third-party form services (Formspree, Basin) instead.</li>
                <li>Search won't work — use a client-side solution like Pagefind or Lunr.js.</li>
                <li>Disable cache plugins before generating for fresh HTML output.</li>
                <li>For large sites (&gt;200 pages) consider increasing PHP <code>max_execution_time</code>.</li>
            </ul>

            <h3 class="wpsp-section-title">🚀 Deploy to GitHub Pages</h3>
            <ul class="wpsp-guide">
                <li>Push to the <code>gh-pages</code> branch of your repo.</li>
                <li>In repo Settings → Pages → set source branch to <code>gh-pages</code>.</li>
                <li>Your site goes live at <code>username.github.io/repo</code>.</li>
            </ul>
        </div>

    </div><!-- /col-side -->

</div><!-- /grid -->
</div><!-- /wrap -->
