<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPSP_Crawler {

    private $output_dir;
    private $base_url;
    private $visited   = array();
    private $queue     = array();
    private $assets    = array();
    private $errors    = array();
    private $log       = array();
    private $max_depth;
    private $exclude   = array();

    public function __construct() {
        $this->output_dir = WPSP_OUTPUT_DIR . '/site';
        $this->base_url   = rtrim( WPSP_Settings::get('base_url') ?: get_site_url(), '/' );
        $this->max_depth  = intval( WPSP_Settings::get('crawl_depth') ?: 5 );

        $exclude_raw = WPSP_Settings::get('exclude_paths');
        if ( $exclude_raw ) {
            $this->exclude = array_filter( array_map( 'trim', explode( "\n", $exclude_raw ) ) );
        }
    }

    public function run() {
        $start = microtime( true );
        $this->cleanup_output();
        wp_mkdir_p( $this->output_dir );

        // Seed URLs
        $this->queue[] = array( 'url' => $this->base_url . '/', 'depth' => 0 );
        $this->queue[] = array( 'url' => $this->base_url . '/sitemap.xml', 'depth' => 0 );

        // Add all published posts + pages
        $posts = get_posts( array(
            'post_type'   => array('post', 'page'),
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields'      => 'ids',
        ) );
        foreach ( $posts as $id ) {
            $this->queue[] = array( 'url' => get_permalink( $id ), 'depth' => 0 );
        }

        // Add taxonomy archives
        $categories = get_categories( array( 'hide_empty' => true ) );
        foreach ( $categories as $cat ) {
            $this->queue[] = array( 'url' => get_category_link( $cat->term_id ), 'depth' => 0 );
        }

        $tags = get_tags( array( 'hide_empty' => true ) );
        foreach ( $tags as $tag ) {
            $this->queue[] = array( 'url' => get_tag_link( $tag->term_id ), 'depth' => 0 );
        }

        // Process queue
        while ( ! empty( $this->queue ) ) {
            $item  = array_shift( $this->queue );
            $url   = $item['url'];
            $depth = $item['depth'];

            if ( isset( $this->visited[ $url ] ) ) continue;
            if ( $this->is_excluded( $url ) ) continue;
            if ( ! $this->is_internal( $url ) ) continue;

            $this->visited[ $url ] = true;
            $this->crawl_url( $url, $depth );
        }

        // Download collected assets
        $this->download_assets();

        $duration = round( microtime( true ) - $start, 1 );
        $this->log[] = 'Completed in ' . $duration . 's — ' . count( $this->visited ) . ' URLs visited, ' . count( $this->assets ) . ' assets';

        return array(
            'pages'    => count( $this->visited ),
            'assets'   => count( $this->assets ),
            'errors'   => $this->errors,
            'log'      => $this->log,
            'duration' => $duration,
        );
    }

    private function crawl_url( $url, $depth ) {
        $this->log[] = "Crawling: $url";

        $response = wp_remote_get( $url, array(
            'timeout'    => 30,
            'user-agent' => 'WP-Static-Push/1.0',
            'sslverify'  => false,
        ) );

        if ( is_wp_error( $response ) ) {
            $this->errors[] = "Failed: $url — " . $response->get_error_message();
            return;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            $this->errors[] = "HTTP $code: $url";
            return;
        }

        $content_type = wp_remote_retrieve_header( $response, 'content-type' );
        $body         = wp_remote_retrieve_body( $response );

        $file_path = $this->url_to_filepath( $url );
        wp_mkdir_p( dirname( $file_path ) );

        if ( strpos( $content_type, 'text/html' ) !== false ) {
            $body = $this->rewrite_html( $body, $url );
            file_put_contents( $file_path, $body );

            if ( $depth < $this->max_depth ) {
                $this->extract_links( $body, $url, $depth );
            }
        } elseif ( strpos( $content_type, 'text/css' ) !== false ) {
            $body = $this->rewrite_css( $body, $url );
            file_put_contents( $file_path, $body );
        } else {
            file_put_contents( $file_path, $body );
        }
    }

    private function rewrite_html( $html, $base ) {
        // Rewrite absolute URLs to relative
        $site_url = get_site_url();
        $html = str_replace( $site_url, '', $html );

        // Fix root-relative links
        // Remove WordPress admin bar if present
        $html = preg_replace( '/<div[^>]*id=["\']wpadminbar["\'][^>]*>.*?<\/div>/si', '', $html );

        // Remove nonces, dynamic forms (WordPress login, comment forms)
        $html = preg_replace( '/<input[^>]*name=["\']_wpnonce["\'][^>]*>/i', '', $html );

        // Collect asset URLs
        preg_match_all( '/(?:src|href)=["\']([^"\']+(?:\.css|\.js|\.png|\.jpg|\.jpeg|\.gif|\.svg|\.webp|\.woff|\.woff2|\.ttf|\.ico))["\']/', $html, $matches );
        foreach ( $matches[1] as $asset_url ) {
            $full = $this->make_absolute( $asset_url, $base );
            if ( $full && $this->is_internal( $full ) ) {
                $this->assets[ $full ] = true;
            }
        }

        return $html;
    }

    private function rewrite_css( $css, $base ) {
        preg_match_all( '/url\(["\']?([^"\')\s]+)["\']?\)/', $css, $matches );
        foreach ( $matches[1] as $asset_url ) {
            $full = $this->make_absolute( $asset_url, $base );
            if ( $full && $this->is_internal( $full ) ) {
                $this->assets[ $full ] = true;
            }
        }
        $site_url = get_site_url();
        return str_replace( $site_url, '', $css );
    }

    private function extract_links( $html, $base, $depth ) {
        preg_match_all( '/<a[^>]+href=["\']([^"\'#?]+)["\']/', $html, $matches );
        foreach ( $matches[1] as $href ) {
            $full = $this->make_absolute( $href, $base );
            if ( $full && $this->is_internal( $full ) && ! isset( $this->visited[ $full ] ) ) {
                $this->queue[] = array( 'url' => $full, 'depth' => $depth + 1 );
            }
        }
    }

    private function download_assets() {
        foreach ( array_keys( $this->assets ) as $url ) {
            if ( isset( $this->visited[ $url ] ) ) continue;
            $this->visited[ $url ] = true;

            $response = wp_remote_get( $url, array(
                'timeout'   => 20,
                'sslverify' => false,
            ) );

            if ( is_wp_error( $response ) ) continue;
            if ( wp_remote_retrieve_response_code( $response ) !== 200 ) continue;

            $body      = wp_remote_retrieve_body( $response );
            $file_path = $this->url_to_filepath( $url );
            wp_mkdir_p( dirname( $file_path ) );
            file_put_contents( $file_path, $body );
        }
    }

    private function url_to_filepath( $url ) {
        $path = str_replace( array( $this->base_url, get_site_url() ), '', $url );
        $path = ltrim( $path, '/' );

        if ( empty( $path ) || substr( $path, -1 ) === '/' ) {
            return $this->output_dir . '/' . rtrim( $path, '/' ) . '/index.html';
        }

        if ( ! pathinfo( $path, PATHINFO_EXTENSION ) ) {
            return $this->output_dir . '/' . $path . '/index.html';
        }

        return $this->output_dir . '/' . $path;
    }

    private function make_absolute( $url, $base ) {
        if ( empty( $url ) ) return false;
        if ( strpos( $url, '//' ) === 0 ) return 'https:' . $url;
        if ( strpos( $url, 'http' ) === 0 ) return $url;
        if ( strpos( $url, '/' ) === 0 ) return rtrim( $this->base_url, '/' ) . $url;
        return rtrim( dirname( $base ), '/' ) . '/' . $url;
    }

    private function is_internal( $url ) {
        return strpos( $url, $this->base_url ) === 0
            || strpos( $url, get_site_url() ) === 0;
    }

    private function is_excluded( $url ) {
        foreach ( $this->exclude as $pattern ) {
            if ( strpos( $url, $pattern ) !== false ) return true;
        }
        // Always exclude admin/login/feed
        $always_exclude = array( '/wp-admin', '/wp-login', 'xmlrpc', '?feed', '/feed/' );
        foreach ( $always_exclude as $ex ) {
            if ( strpos( $url, $ex ) !== false ) return true;
        }
        return false;
    }

    private function cleanup_output() {
        if ( file_exists( $this->output_dir ) ) {
            $this->rrmdir( $this->output_dir );
        }
    }

    private function rrmdir( $dir ) {
        if ( ! is_dir( $dir ) ) return;
        $objects = scandir( $dir );
        foreach ( $objects as $object ) {
            if ( $object === '.' || $object === '..' ) continue;
            $path = $dir . '/' . $object;
            is_dir( $path ) ? $this->rrmdir( $path ) : unlink( $path );
        }
        rmdir( $dir );
    }

    public function get_output_dir() {
        return $this->output_dir;
    }
}
