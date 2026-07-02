<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap wpsp-wrap">

<div class="wpsp-header">
    <div class="wpsp-header-inner">
        <div class="wpsp-logo">
            <span class="dashicons dashicons-cloud-upload"></span>
            <h1><?php esc_html_e( 'Static Push', 'static-push' ); ?></h1>
        </div>
        <span class="wpsp-version">v<?php echo esc_html( WPSP_VERSION ); ?></span>
    </div>
</div>

<?php if ( isset( $_GET['saved'] ) ): // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display flag, no state change. ?>
<div class="notice notice-success is-dismissible"><p><?php esc_html_e( '✅ Settings saved.', 'static-push' ); ?></p></div>
<?php endif; ?>

<div class="wpsp-grid">

    <!-- ===================== LEFT COLUMN ===================== -->
    <div class="wpsp-col-main">

        <!-- GENERATE + DEPLOY CARD -->
        <div class="wpsp-card wpsp-card-primary">
            <h2><?php esc_html_e( '🚀 Generate & Deploy', 'static-push' ); ?></h2>
            <p class="wpsp-muted"><?php esc_html_e( 'Crawl your site, build static HTML, then download or push to GitHub.', 'static-push' ); ?></p>

            <div class="wpsp-status-bar" id="wpsp-status-bar">
                <span id="wpsp-status-text"><?php esc_html_e( 'Ready', 'static-push' ); ?></span>
                <div class="wpsp-progress" id="wpsp-progress" style="display:none;">
                    <div class="wpsp-progress-inner" id="wpsp-progress-inner"></div>
                </div>
            </div>

            <div class="wpsp-actions">
                <button class="wpsp-btn wpsp-btn-primary" id="wpsp-generate">
                    <span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Generate Static Site', 'static-push' ); ?>
                </button>
                <button class="wpsp-btn wpsp-btn-github" id="wpsp-push-github" disabled>
                    <span class="dashicons dashicons-cloud-upload"></span> <?php esc_html_e( 'Push to GitHub', 'static-push' ); ?>
                </button>
                <button class="wpsp-btn wpsp-btn-secondary" id="wpsp-download-zip" disabled>
                    <span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Download ZIP', 'static-push' ); ?>
                </button>
            </div>

            <div class="wpsp-result" id="wpsp-result" style="display:none;"></div>

            <details class="wpsp-log-panel" id="wpsp-log-panel" style="display:none;">
                <summary id="wpsp-log-summary"><?php esc_html_e( 'View detailed log', 'static-push' ); ?></summary>
                <div class="wpsp-log-entries" id="wpsp-log-entries"></div>
            </details>

            <div class="wpsp-last-run" id="wpsp-last-run">
                <span class="wpsp-muted"><?php esc_html_e( 'Checking status…', 'static-push' ); ?></span>
            </div>
        </div>

        <!-- SETTINGS CARD -->
        <div class="wpsp-card">
            <h2><?php esc_html_e( '⚙️ Settings', 'static-push' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                <?php wp_nonce_field('wpsp_save_settings'); ?>
                <input type="hidden" name="action" value="wpsp_save_settings">

                <h3 class="wpsp-section-title"><?php esc_html_e( '🐙 GitHub', 'static-push' ); ?></h3>
                <div class="wpsp-fields">
                    <div class="wpsp-field">
                        <label><?php esc_html_e( 'Personal Access Token', 'static-push' ); ?>
                            <a href="https://github.com/settings/tokens/new?scopes=repo&description=Static+Push" target="_blank" class="wpsp-link"><?php esc_html_e( 'Generate →', 'static-push' ); ?></a>
                        </label>
                        <input type="password" name="github_token"
                            value="<?php echo esc_attr($settings['github_token']); ?>"
                            placeholder="ghp_xxxxxxxxxxxx" autocomplete="off">
                        <small><?php
                            printf(
                                /* translators: 1: the "repo" scope name, 2: the "public_repo" scope name. */
                                esc_html__( 'Needs %1$s scope (or %2$s for public repos)', 'static-push' ),
                                '<code>repo</code>',
                                '<code>public_repo</code>'
                            );
                        ?></small>
                    </div>
                    <div class="wpsp-field">
                        <label><?php esc_html_e( 'Repository', 'static-push' ); ?> <small>(<?php esc_html_e( 'owner/repo', 'static-push' ); ?>)</small></label>
                        <input type="text" name="github_repo"
                            value="<?php echo esc_attr($settings['github_repo']); ?>"
                            placeholder="yourname/your-site">
                    </div>
                    <div class="wpsp-fields-row">
                        <div class="wpsp-field">
                            <label><?php esc_html_e( 'Branch', 'static-push' ); ?></label>
                            <input type="text" name="github_branch"
                                value="<?php echo esc_attr($settings['github_branch'] ?: 'gh-pages'); ?>"
                                placeholder="gh-pages">
                        </div>
                        <div class="wpsp-field">
                            <label><?php esc_html_e( 'Sub-directory', 'static-push' ); ?> <small>(<?php esc_html_e( 'optional', 'static-push' ); ?>)</small></label>
                            <input type="text" name="github_subdir"
                                value="<?php echo esc_attr($settings['github_subdir']); ?>"
                                placeholder="docs">
                        </div>
                    </div>
                    <div class="wpsp-inline">
                        <button type="button" class="wpsp-btn wpsp-btn-sm" id="wpsp-test-github">
                            <?php esc_html_e( 'Test Connection', 'static-push' ); ?>
                        </button>
                        <span id="wpsp-github-test-result"></span>
                    </div>
                </div>

                <h3 class="wpsp-section-title"><?php esc_html_e( '🌐 Site', 'static-push' ); ?></h3>
                <div class="wpsp-fields">
                    <div class="wpsp-field">
                        <label><?php esc_html_e( 'Production Base URL', 'static-push' ); ?> <small>(<?php esc_html_e( 'optional — defaults to WordPress site URL', 'static-push' ); ?>)</small></label>
                        <input type="url" name="base_url"
                            value="<?php echo esc_attr($settings['base_url']); ?>"
                            placeholder="<?php echo esc_attr( get_site_url() ); ?>">
                        <small><?php esc_html_e( 'Use this if your static site will be hosted at a different URL (e.g. GitHub Pages URL)', 'static-push' ); ?></small>
                    </div>
                    <div class="wpsp-field">
                        <label><?php esc_html_e( 'Exclude Paths', 'static-push' ); ?> <small>(<?php esc_html_e( 'one per line', 'static-push' ); ?>)</small></label>
                        <textarea name="exclude_paths" rows="4"
                            placeholder="/cart&#10;/checkout&#10;/my-account"><?php echo esc_textarea($settings['exclude_paths']); ?></textarea>
                    </div>
                    <div class="wpsp-field">
                        <label><?php esc_html_e( 'Crawl Depth', 'static-push' ); ?></label>
                        <input type="number" name="crawl_depth" min="1" max="20"
                            value="<?php echo intval($settings['crawl_depth'] ?: 5); ?>">
                        <small><?php esc_html_e( 'How many levels deep to follow links (5 is usually enough)', 'static-push' ); ?></small>
                    </div>
                </div>

                <h3 class="wpsp-section-title"><?php esc_html_e( '🔍 SEO Files', 'static-push' ); ?></h3>
                <div class="wpsp-fields">
                    <div class="wpsp-checks">
                        <label class="wpsp-check">
                            <input type="checkbox" name="generate_sitemap" value="1"
                                <?php checked($settings['generate_sitemap'], '1'); ?>>
                            <span><?php
                                /* translators: %s: the sitemap.xml file name. */
                                printf( esc_html__( 'Generate %s', 'static-push' ), '<code>sitemap.xml</code>' );
                            ?></span>
                        </label>
                        <label class="wpsp-check">
                            <input type="checkbox" name="generate_robots" value="1"
                                <?php checked($settings['generate_robots'], '1'); ?>>
                            <span><?php
                                /* translators: %s: the robots.txt file name. */
                                printf( esc_html__( 'Generate %s', 'static-push' ), '<code>robots.txt</code>' );
                            ?></span>
                        </label>
                        <label class="wpsp-check">
                            <input type="checkbox" name="generate_404" value="1"
                                <?php checked($settings['generate_404'], '1'); ?>>
                            <span><?php
                                /* translators: %s: the 404.html file name. */
                                printf( esc_html__( 'Generate %s', 'static-push' ), '<code>404.html</code>' );
                            ?></span>
                        </label>
                    </div>
                </div>

                <div class="wpsp-form-footer">
                    <?php submit_button( __( 'Save Settings', 'static-push' ), 'primary', 'submit', false ); ?>
                </div>
            </form>
        </div>

    </div><!-- /col-main -->

    <!-- ===================== RIGHT COLUMN ===================== -->
    <div class="wpsp-col-side">

        <!-- SITE INFO CARD -->
        <div class="wpsp-card">
            <h2><?php esc_html_e( '🔍 Site Analysis', 'static-push' ); ?></h2>
            <p class="wpsp-muted"><?php esc_html_e( 'How your current setup will affect static generation.', 'static-push' ); ?></p>

            <div class="wpsp-site-meta">
                <div class="wpsp-meta-row">
                    <span><?php esc_html_e( 'WordPress', 'static-push' ); ?></span>
                    <strong>v<?php echo esc_html($site_info['wp_version']); ?></strong>
                </div>
                <div class="wpsp-meta-row">
                    <span><?php esc_html_e( 'Posts', 'static-push' ); ?></span>
                    <strong><?php echo intval($site_info['total_posts']); ?></strong>
                </div>
                <div class="wpsp-meta-row">
                    <span><?php esc_html_e( 'Pages', 'static-push' ); ?></span>
                    <strong><?php echo intval($site_info['total_pages']); ?></strong>
                </div>
            </div>

            <h3 class="wpsp-section-title"><?php esc_html_e( 'Active Theme', 'static-push' ); ?></h3>
            <div class="wpsp-theme-row">
                <div class="wpsp-theme-info">
                    <strong><?php echo esc_html($site_info['theme']['name']); ?></strong>
                    <small>v<?php echo esc_html($site_info['theme']['version']); ?></small>
                </div>
                <span class="wpsp-compat-note"><?php echo esc_html($site_info['theme']['note']); ?></span>
            </div>

            <h3 class="wpsp-section-title"><?php
                /* translators: %d: number of active plugins. */
                printf( esc_html__( 'Active Plugins (%d)', 'static-push' ), count($site_info['plugins']) );
            ?></h3>
            <div class="wpsp-plugin-list">
                <?php if ( empty($site_info['plugins']) ): ?>
                    <p class="wpsp-muted"><?php esc_html_e( 'No active plugins.', 'static-push' ); ?></p>
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
            <h2><?php esc_html_e( '📖 Quick Guide', 'static-push' ); ?></h2>
            <ol class="wpsp-guide">
                <li><?php esc_html_e( 'Set your GitHub token + repo, save settings.', 'static-push' ); ?></li>
                <li><?php esc_html_e( 'Optionally set a Production URL if your static site will be hosted elsewhere.', 'static-push' ); ?></li>
                <li><?php echo wp_kses( __( 'Click <strong>Generate Static Site</strong> — this crawls every page.', 'static-push' ), array( 'strong' => array() ) ); ?></li>
                <li><?php echo wp_kses( __( 'Then either <strong>Push to GitHub</strong> or <strong>Download ZIP</strong>.', 'static-push' ), array( 'strong' => array() ) ); ?></li>
            </ol>

            <h3 class="wpsp-section-title"><?php esc_html_e( '⚠️ Limitations', 'static-push' ); ?></h3>
            <ul class="wpsp-guide">
                <li><?php esc_html_e( 'Dynamic forms (Contact Form 7, WooCommerce checkout) won\'t function in static output — use third-party form services (Formspree, Basin) instead.', 'static-push' ); ?></li>
                <li><?php esc_html_e( 'Search won\'t work — use a client-side solution like Pagefind or Lunr.js.', 'static-push' ); ?></li>
                <li><?php esc_html_e( 'Disable cache plugins before generating for fresh HTML output.', 'static-push' ); ?></li>
                <li><?php echo wp_kses( __( 'For large sites (&gt;200 pages) consider increasing PHP <code>max_execution_time</code>.', 'static-push' ), array( 'code' => array() ) ); ?></li>
            </ul>

            <h3 class="wpsp-section-title"><?php esc_html_e( '🚀 Deploy to GitHub Pages', 'static-push' ); ?></h3>
            <ul class="wpsp-guide">
                <li><?php echo wp_kses( __( 'Push to the <code>gh-pages</code> branch of your repo.', 'static-push' ), array( 'code' => array() ) ); ?></li>
                <li><?php echo wp_kses( __( 'In repo Settings → Pages → set source branch to <code>gh-pages</code>.', 'static-push' ), array( 'code' => array() ) ); ?></li>
                <li><?php echo wp_kses( __( 'Your site goes live at <code>username.github.io/repo</code>.', 'static-push' ), array( 'code' => array() ) ); ?></li>
            </ul>
        </div>

    </div><!-- /col-side -->

</div><!-- /grid -->
</div><!-- /wrap -->
