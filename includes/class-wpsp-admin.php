<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPSP_Admin {

    public function __construct() {
        add_action( 'admin_menu',             array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_assets' ) );
        add_action( 'admin_post_wpsp_save_settings', array( $this, 'save_settings' ) );
    }

    public function register_menu() {
        add_menu_page(
            __( 'Static Push', 'static-push' ),
            __( 'Static Push', 'static-push' ),
            'manage_options',
            'static-push',
            array( $this, 'render_page' ),
            'dashicons-cloud-upload',
            80
        );
    }

    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'static-push' ) === false ) return;

        wp_enqueue_style(
            'wpsp-admin',
            WPSP_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            WPSP_VERSION
        );
        wp_enqueue_script(
            'wpsp-admin',
            WPSP_PLUGIN_URL . 'admin/js/admin.js',
            array( 'jquery' ),
            WPSP_VERSION,
            true
        );
        wp_localize_script( 'wpsp-admin', 'WPSP', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wpsp_nonce'),
        ) );
    }

    public function render_page() {
        $settings  = WPSP_Settings::get();
        $site_info = WPSP_Settings::get_site_info();
        include WPSP_PLUGIN_DIR . 'templates/admin-page.php';
    }

    public function save_settings() {
        if ( ! current_user_can('manage_options') ) wp_die( esc_html__( 'Unauthorized', 'static-push' ) );
        check_admin_referer('wpsp_save_settings');

        WPSP_Settings::set( array(
            'github_token'     => sanitize_text_field( wp_unslash( $_POST['github_token'] ?? '' ) ),
            'github_repo'      => sanitize_text_field( wp_unslash( $_POST['github_repo'] ?? '' ) ),
            'github_branch'    => sanitize_text_field( wp_unslash( $_POST['github_branch'] ?? 'gh-pages' ) ),
            'github_subdir'    => sanitize_text_field( wp_unslash( $_POST['github_subdir'] ?? '' ) ),
            'base_url'         => esc_url_raw( wp_unslash( $_POST['base_url'] ?? '' ) ),
            'exclude_paths'    => sanitize_textarea_field( wp_unslash( $_POST['exclude_paths'] ?? '' ) ),
            'generate_sitemap' => isset( $_POST['generate_sitemap'] ) ? '1' : '0',
            'generate_robots'  => isset( $_POST['generate_robots'] ) ? '1' : '0',
            'generate_404'     => isset( $_POST['generate_404'] ) ? '1' : '0',
            'crawl_depth'      => intval( $_POST['crawl_depth'] ?? 5 ),
        ) );

        wp_safe_redirect( admin_url('admin.php?page=static-push&saved=1') );
        exit;
    }
}
