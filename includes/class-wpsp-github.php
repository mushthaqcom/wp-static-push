<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPSP_GitHub {

    private $token;
    private $repo;
    private $branch;
    private $subdir;
    private $api_base = 'https://api.github.com';

    public function __construct() {
        $this->token  = WPSP_Settings::get('github_token');
        $this->repo   = WPSP_Settings::get('github_repo');   // format: owner/repo
        $this->branch = WPSP_Settings::get('github_branch') ?: 'gh-pages';
        $this->subdir = trim( WPSP_Settings::get('github_subdir'), '/' );
    }

    public function test_connection() {
        if ( empty( $this->token ) || empty( $this->repo ) ) {
            return new WP_Error( 'config', 'GitHub token and repo are required.' );
        }

        $response = $this->api_get( '/repos/' . $this->repo );
        if ( is_wp_error( $response ) ) return $response;

        return array(
            'name'        => $response['name'],
            'full_name'   => $response['full_name'],
            'visibility'  => $response['visibility'],
            'html_url'    => $response['html_url'],
        );
    }

    public function push_directory( $source_dir ) {
        if ( empty( $this->token ) || empty( $this->repo ) ) {
            return new WP_Error( 'config', 'GitHub token and repo are required.' );
        }

        $pushed  = 0;
        $skipped = 0;
        $errors  = array();

        // Collect all files
        $files = $this->collect_files( $source_dir );

        foreach ( $files as $local_path => $repo_path ) {
            $content  = base64_encode( file_get_contents( $local_path ) );
            $api_path = '/repos/' . $this->repo . '/contents/' . $repo_path;

            // Check if file exists (to get SHA for update)
            $existing = $this->api_get( $api_path . '?ref=' . $this->branch );
            $sha      = ( ! is_wp_error( $existing ) && isset( $existing['sha'] ) ) ? $existing['sha'] : null;

            $body = array(
                'message' => 'WP Static Push: update ' . $repo_path,
                'content' => $content,
                'branch'  => $this->branch,
            );
            if ( $sha ) {
                $body['sha'] = $sha;
            }

            $result = $this->api_put( $api_path, $body );

            if ( is_wp_error( $result ) ) {
                $errors[] = $repo_path . ': ' . $result->get_error_message();
            } else {
                $pushed++;
            }

            // Small delay to avoid secondary rate limits
            usleep( 100000 ); // 100ms
        }

        WPSP_Settings::set( array( 'last_pushed' => current_time('mysql') ) );

        return array(
            'pushed'  => $pushed,
            'skipped' => $skipped,
            'errors'  => $errors,
            'repo_url' => 'https://github.com/' . $this->repo,
            'pages_url' => $this->get_pages_url(),
        );
    }

    private function collect_files( $source_dir ) {
        $files   = array();
        $source  = realpath( $source_dir );
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $source, RecursiveDirectoryIterator::SKIP_DOTS )
        );

        foreach ( $iterator as $file ) {
            if ( $file->isDir() ) continue;
            $local      = $file->getRealPath();
            $relative   = substr( $local, strlen( $source ) + 1 );
            $relative   = str_replace( '\\', '/', $relative );
            $repo_path  = $this->subdir ? $this->subdir . '/' . $relative : $relative;
            $files[ $local ] = $repo_path;
        }

        return $files;
    }

    private function get_pages_url() {
        // Common GitHub Pages URL patterns
        $parts = explode( '/', $this->repo );
        if ( count( $parts ) === 2 ) {
            list( $owner, $repo ) = $parts;
            // Check if it's a user/org pages repo
            if ( strtolower( $repo ) === strtolower( $owner ) . '.github.io' ) {
                return 'https://' . strtolower( $owner ) . '.github.io';
            }
            return 'https://' . strtolower( $owner ) . '.github.io/' . $repo;
        }
        return '';
    }

    private function api_get( $path ) {
        $response = wp_remote_get( $this->api_base . $path, array(
            'headers' => $this->get_headers(),
            'timeout' => 15,
        ) );

        return $this->parse_response( $response );
    }

    private function api_put( $path, $body ) {
        $response = wp_remote_request( $this->api_base . $path, array(
            'method'  => 'PUT',
            'headers' => $this->get_headers(),
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        ) );

        return $this->parse_response( $response );
    }

    private function get_headers() {
        return array(
            'Authorization' => 'Bearer ' . $this->token,
            'Accept'        => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
            'Content-Type'  => 'application/json',
            'User-Agent'    => 'WP-Static-Push/1.0',
        );
    }

    private function parse_response( $response ) {
        if ( is_wp_error( $response ) ) return $response;

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 ) {
            $msg = isset( $body['message'] ) ? $body['message'] : "HTTP $code";
            return new WP_Error( 'github_api', $msg );
        }

        return $body;
    }

    public function get_repo() { return $this->repo; }
    public function get_branch() { return $this->branch; }
}
