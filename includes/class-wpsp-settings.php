<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPSP_Settings {

    const OPTION_KEY = 'wpsp_settings';

    public static function install() {
        $defaults = array(
            'github_token'       => '',
            'github_repo'        => '',
            'github_branch'      => 'gh-pages',
            'github_subdir'      => '',
            'base_url'           => '',
            'exclude_paths'      => '',
            'generate_sitemap'   => '1',
            'generate_robots'    => '1',
            'generate_404'       => '1',
            'crawl_depth'        => '5',
            'last_generated'     => '',
            'last_pushed'        => '',
        );
        if ( ! get_option( self::OPTION_KEY ) ) {
            add_option( self::OPTION_KEY, $defaults );
        }
    }

    public static function get( $key = null ) {
        $options = get_option( self::OPTION_KEY, array() );
        if ( $key ) {
            return isset( $options[ $key ] ) ? $options[ $key ] : '';
        }
        return $options;
    }

    public static function set( $data ) {
        $current = get_option( self::OPTION_KEY, array() );
        $updated  = array_merge( $current, $data );
        update_option( self::OPTION_KEY, $updated );
    }

    public static function get_site_info() {
        $active_plugins = get_option( 'active_plugins', array() );
        $all_plugins    = get_plugins();
        $current_theme  = wp_get_theme();

        $plugins = array();
        foreach ( $active_plugins as $plugin_file ) {
            if ( isset( $all_plugins[ $plugin_file ] ) ) {
                $p = $all_plugins[ $plugin_file ];
                $plugins[] = array(
                    'name'    => $p['Name'],
                    'version' => $p['Version'],
                    'author'  => $p['Author'],
                    'note'    => self::analyze_plugin_compatibility( $p['Name'] ),
                );
            }
        }

        return array(
            'plugins' => $plugins,
            'theme'   => array(
                'name'    => $current_theme->get('Name'),
                'version' => $current_theme->get('Version'),
                'author'  => $current_theme->get('Author'),
                'note'    => self::analyze_theme_compatibility( $current_theme->get('Name') ),
            ),
            'wp_version'    => get_bloginfo('version'),
            'site_url'      => get_site_url(),
            'total_posts'   => wp_count_posts()->publish,
            'total_pages'   => wp_count_posts('page')->publish,
        );
    }

    private static function analyze_plugin_compatibility( $name ) {
        $dynamic_plugins = array(
            'WooCommerce', 'Contact Form 7', 'Gravity Forms', 'WPForms',
            'bbPress', 'BuddyPress', 'MemberPress', 'WP Job Manager',
            'Events Calendar', 'LearnDash', 'LifterLMS', 'Easy Digital Downloads',
        );
        $seo_plugins = array(
            'Yoast SEO', 'All in One SEO', 'Rank Math', 'SEOPress',
        );
        $cache_plugins = array(
            'WP Super Cache', 'W3 Total Cache', 'WP Rocket', 'LiteSpeed Cache',
        );

        foreach ( $dynamic_plugins as $dp ) {
            if ( stripos( $name, $dp ) !== false ) {
                return '⚠️ Dynamic — some features won\'t work in static output';
            }
        }
        foreach ( $seo_plugins as $sp ) {
            if ( stripos( $name, $sp ) !== false ) {
                return '✅ SEO meta tags will be captured in static HTML';
            }
        }
        foreach ( $cache_plugins as $cp ) {
            if ( stripos( $name, $cp ) !== false ) {
                return 'ℹ️ Cache plugin — disable before generating for fresh output';
            }
        }
        return '✅ Should work fine';
    }

    private static function analyze_theme_compatibility( $name ) {
        $known_builders = array( 'Elementor', 'Divi', 'Avada', 'OceanWP', 'Astra', 'GeneratePress' );
        foreach ( $known_builders as $b ) {
            if ( stripos( $name, $b ) !== false ) {
                return '✅ Page builder theme — static HTML will be fully rendered';
            }
        }
        return '✅ Compatible';
    }
}
