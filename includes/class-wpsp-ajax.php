<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPSP_Ajax {

    public function __construct() {
        add_action( 'wp_ajax_wpsp_generate',        array( $this, 'handle_generate' ) );
        add_action( 'wp_ajax_wpsp_push_github',     array( $this, 'handle_push_github' ) );
        add_action( 'wp_ajax_wpsp_download_zip',    array( $this, 'handle_download_zip' ) );
        add_action( 'wp_ajax_wpsp_test_github',     array( $this, 'handle_test_github' ) );
        add_action( 'wp_ajax_wpsp_get_status',      array( $this, 'handle_get_status' ) );
    }

    public function handle_generate() {
        $this->verify_nonce();

        @set_time_limit( 300 );
        @ini_set( 'memory_limit', '256M' );

        $crawler = new WPSP_Crawler();
        $result  = $crawler->run();

        // Generate SEO files
        $seo      = new WPSP_SEO( $crawler->get_output_dir() );
        $seo_files = $seo->generate_all();

        WPSP_Settings::set( array( 'last_generated' => current_time('mysql') ) );

        wp_send_json_success( array(
            'pages'     => $result['pages'],
            'assets'    => $result['assets'],
            'seo_files' => $seo_files,
            'errors'    => $result['errors'],
            'log'       => array_slice( $result['log'], 0, 300 ),
            'duration'  => $result['duration'],
            'message'   => sprintf(
                'Generated %d pages, %d assets in %ss.',
                $result['pages'],
                $result['assets'],
                $result['duration']
            ),
        ) );
    }

    public function handle_push_github() {
        $this->verify_nonce();

        $output_dir = WPSP_OUTPUT_DIR . '/site';
        if ( ! file_exists( $output_dir ) ) {
            wp_send_json_error( 'No static site generated yet. Please generate first.' );
        }

        @set_time_limit( 600 );

        $github = new WPSP_GitHub();
        $result = $github->push_directory( $output_dir );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( array(
            'pushed'     => $result['pushed'],
            'errors'     => $result['errors'],
            'commit_sha' => $result['commit_sha'],
            'commit_url' => $result['commit_url'],
            'repo_url'   => $result['repo_url'],
            'pages_url'  => $result['pages_url'],
            'duration'   => $result['duration'],
            'log'        => $result['log'],
            'message'    => sprintf(
                'Pushed %d files in a single commit to branch "%s" in %ss.',
                $result['pushed'],
                WPSP_Settings::get('github_branch'),
                $result['duration']
            ),
        ) );
    }

    public function handle_download_zip() {
        $this->verify_nonce();

        $output_dir = WPSP_OUTPUT_DIR . '/site';
        if ( ! file_exists( $output_dir ) ) {
            wp_send_json_error( 'No static site generated yet. Please generate first.' );
        }

        $zip_file = WPSP_Zip::create( $output_dir );

        if ( is_wp_error( $zip_file ) ) {
            wp_send_json_error( $zip_file->get_error_message() );
        }

        $download_url = WPSP_Zip::get_download_url( $zip_file );

        wp_send_json_success( array(
            'download_url' => $download_url,
            'filename'     => basename( $zip_file ),
            'size'         => size_format( filesize( $zip_file ) ),
        ) );
    }

    public function handle_test_github() {
        $this->verify_nonce();

        $github = new WPSP_GitHub();
        $result = $github->test_connection();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( array(
            'repo'       => $result['full_name'],
            'visibility' => $result['visibility'],
            'url'        => $result['html_url'],
            'message'    => "✅ Connected to {$result['full_name']} ({$result['visibility']})",
        ) );
    }

    public function handle_get_status() {
        $this->verify_nonce();

        $output_dir  = WPSP_OUTPUT_DIR . '/site';
        $has_site    = file_exists( $output_dir );
        $file_count  = 0;

        if ( $has_site ) {
            $iterator   = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $output_dir ) );
            foreach ( $iterator as $f ) {
                if ( ! $f->isDir() ) $file_count++;
            }
        }

        wp_send_json_success( array(
            'has_site'       => $has_site,
            'file_count'     => $file_count,
            'last_generated' => WPSP_Settings::get('last_generated'),
            'last_pushed'    => WPSP_Settings::get('last_pushed'),
        ) );
    }

    private function verify_nonce() {
        if ( ! current_user_can('manage_options') ) {
            wp_send_json_error( 'Unauthorized' );
        }
        if ( ! check_ajax_referer( 'wpsp_nonce', 'nonce', false ) ) {
            wp_send_json_error( 'Invalid nonce' );
        }
    }
}
