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
        $this->repo   = WPSP_Settings::get('github_repo');
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
            'name'       => $response['name'],
            'full_name'  => $response['full_name'],
            'visibility' => $response['visibility'],
            'html_url'   => $response['html_url'],
        );
    }

    /**
     * Push all files as a single atomic commit using the Git Tree API.
     * One commit = one Cloudflare Pages deployment trigger.
     */
    public function push_directory( $source_dir ) {
        if ( empty( $this->token ) || empty( $this->repo ) ) {
            return new WP_Error( 'config', 'GitHub token and repo are required.' );
        }

        $log    = array();
        $errors = array();
        $start  = microtime( true );

        // Collect all local files
        $files = $this->collect_files( $source_dir );
        if ( empty( $files ) ) {
            return new WP_Error( 'empty', 'No files found to push.' );
        }
        $log[] = 'Collected ' . count( $files ) . ' files from output directory';

        // Get current branch tip (may not exist yet)
        $base_commit_sha = null;
        $ref_data        = $this->api_get( '/repos/' . $this->repo . '/git/ref/heads/' . $this->branch );
        if ( ! is_wp_error( $ref_data ) && isset( $ref_data['object']['sha'] ) ) {
            $base_commit_sha = $ref_data['object']['sha'];
            $log[] = 'Branch "' . $this->branch . '" exists at ' . substr( $base_commit_sha, 0, 7 );
        } else {
            $log[] = 'Branch "' . $this->branch . '" not found — will be created';
        }

        // Step 1: Create a blob for every file
        $tree_items  = array();
        $blob_errors = 0;
        $total_files = count( $files );
        $log[] = 'Creating blobs for ' . $total_files . ' files…';

        foreach ( $files as $local_path => $repo_path ) {
            $content = file_get_contents( $local_path );
            if ( $content === false ) {
                $errors[] = '[ERROR] Could not read file: ' . $repo_path;
                $blob_errors++;
                continue;
            }

            $blob = $this->api_post( '/repos/' . $this->repo . '/git/blobs', array(
                'content'  => base64_encode( $content ),
                'encoding' => 'base64',
            ) );

            if ( is_wp_error( $blob ) ) {
                $errors[] = '[ERROR] Blob failed for ' . $repo_path . ': ' . $blob->get_error_message();
                $blob_errors++;
                continue;
            }

            $tree_items[] = array(
                'path' => $repo_path,
                'mode' => '100644',
                'type' => 'blob',
                'sha'  => $blob['sha'],
            );
        }

        $pushed_count = count( $tree_items );
        $log[] = 'Created ' . $pushed_count . ' blobs' . ( $blob_errors ? " ({$blob_errors} failed)" : ' — no errors' );

        if ( empty( $tree_items ) ) {
            return new WP_Error( 'blobs', 'No blobs could be created. Check PHP error log.' );
        }

        // Step 2: Create a git tree (no base_tree — fresh tree replaces all content)
        $tree = $this->api_post( '/repos/' . $this->repo . '/git/trees', array(
            'tree' => $tree_items,
        ) );
        if ( is_wp_error( $tree ) ) {
            return new WP_Error( 'tree', 'Tree creation failed: ' . $tree->get_error_message() );
        }
        $log[] = 'Created git tree: ' . substr( $tree['sha'], 0, 7 );

        // Step 3: Create a single commit
        $commit_msg  = 'WP Static Push: ' . gmdate( 'Y-m-d H:i' ) . ' UTC — ' . $pushed_count . ' files';
        $commit_body = array(
            'message' => $commit_msg,
            'tree'    => $tree['sha'],
            'parents' => $base_commit_sha ? array( $base_commit_sha ) : array(),
        );
        $commit = $this->api_post( '/repos/' . $this->repo . '/git/commits', $commit_body );
        if ( is_wp_error( $commit ) ) {
            return new WP_Error( 'commit', 'Commit creation failed: ' . $commit->get_error_message() );
        }
        $commit_sha = $commit['sha'];
        $log[] = 'Created commit: ' . substr( $commit_sha, 0, 7 );

        // Step 4: Update or create the branch ref
        if ( $base_commit_sha ) {
            $ref_result = $this->api_patch(
                '/repos/' . $this->repo . '/git/refs/heads/' . $this->branch,
                array( 'sha' => $commit_sha, 'force' => false )
            );
        } else {
            $ref_result = $this->api_post(
                '/repos/' . $this->repo . '/git/refs',
                array( 'ref' => 'refs/heads/' . $this->branch, 'sha' => $commit_sha )
            );
        }
        if ( is_wp_error( $ref_result ) ) {
            return new WP_Error( 'ref', 'Branch ref update failed: ' . $ref_result->get_error_message() );
        }
        $log[] = 'Updated branch "' . $this->branch . '" → ' . substr( $commit_sha, 0, 7 );

        $duration = round( microtime( true ) - $start, 1 );
        $log[] = 'Completed in ' . $duration . 's';

        WPSP_Settings::set( array( 'last_pushed' => current_time( 'mysql' ) ) );

        return array(
            'pushed'     => $pushed_count,
            'errors'     => $errors,
            'commit_sha' => $commit_sha,
            'commit_url' => 'https://github.com/' . $this->repo . '/commit/' . $commit_sha,
            'repo_url'   => 'https://github.com/' . $this->repo,
            'pages_url'  => $this->get_pages_url(),
            'duration'   => $duration,
            'log'        => $log,
        );
    }

    private function collect_files( $source_dir ) {
        $files    = array();
        $source   = realpath( $source_dir );
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $source, RecursiveDirectoryIterator::SKIP_DOTS )
        );
        foreach ( $iterator as $file ) {
            if ( $file->isDir() ) continue;
            $local     = $file->getRealPath();
            $relative  = substr( $local, strlen( $source ) + 1 );
            $relative  = str_replace( '\\', '/', $relative );
            $repo_path = $this->subdir ? $this->subdir . '/' . $relative : $relative;
            $files[ $local ] = $repo_path;
        }
        return $files;
    }

    private function get_pages_url() {
        $parts = explode( '/', $this->repo );
        if ( count( $parts ) === 2 ) {
            list( $owner, $repo ) = $parts;
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
            'timeout' => 20,
        ) );
        return $this->parse_response( $response );
    }

    private function api_post( $path, $body ) {
        $response = wp_remote_post( $this->api_base . $path, array(
            'headers' => $this->get_headers(),
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        ) );
        return $this->parse_response( $response );
    }

    private function api_patch( $path, $body ) {
        $response = wp_remote_request( $this->api_base . $path, array(
            'method'  => 'PATCH',
            'headers' => $this->get_headers(),
            'body'    => wp_json_encode( $body ),
            'timeout' => 20,
        ) );
        return $this->parse_response( $response );
    }

    private function get_headers() {
        return array(
            'Authorization'        => 'Bearer ' . $this->token,
            'Accept'               => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
            'Content-Type'         => 'application/json',
            'User-Agent'           => 'WP-Static-Push/1.0',
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

    public function get_repo()   { return $this->repo; }
    public function get_branch() { return $this->branch; }
}
