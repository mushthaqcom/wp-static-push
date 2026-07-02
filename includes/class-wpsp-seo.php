<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WPSP_SEO {

    private $output_dir;
    private $base_url;

    public function __construct( $output_dir ) {
        $this->output_dir = $output_dir;
        $this->base_url   = rtrim( WPSP_Settings::get('base_url') ?: get_site_url(), '/' );
    }

    public function generate_all() {
        $generated = array();

        if ( WPSP_Settings::get('generate_sitemap') ) {
            $this->generate_sitemap();
            $generated[] = 'sitemap.xml';
        }

        if ( WPSP_Settings::get('generate_robots') ) {
            $this->generate_robots();
            $generated[] = 'robots.txt';
        }

        if ( WPSP_Settings::get('generate_404') ) {
            $this->generate_404();
            $generated[] = '404.html';
        }

        $this->generate_htaccess();
        $generated[] = '.htaccess';

        return $generated;
    }

    private function generate_sitemap() {
        $urls = array();

        // Homepage
        $urls[] = array(
            'loc'        => $this->base_url . '/',
            'changefreq' => 'daily',
            'priority'   => '1.0',
            'lastmod'    => gmdate('Y-m-d'),
        );

        // Posts
        $posts = get_posts( array(
            'post_type'   => 'post',
            'post_status' => 'publish',
            'numberposts' => -1,
        ) );
        foreach ( $posts as $post ) {
            $urls[] = array(
                'loc'        => $this->rewrite_url( get_permalink( $post->ID ) ),
                'changefreq' => 'weekly',
                'priority'   => '0.8',
                'lastmod'    => gmdate( 'Y-m-d', strtotime( $post->post_modified ) ),
            );
        }

        // Pages
        $pages = get_posts( array(
            'post_type'   => 'page',
            'post_status' => 'publish',
            'numberposts' => -1,
        ) );
        foreach ( $pages as $page ) {
            $urls[] = array(
                'loc'        => $this->rewrite_url( get_permalink( $page->ID ) ),
                'changefreq' => 'monthly',
                'priority'   => '0.7',
                'lastmod'    => gmdate( 'Y-m-d', strtotime( $page->post_modified ) ),
            );
        }

        // Categories
        $categories = get_categories( array( 'hide_empty' => true ) );
        foreach ( $categories as $cat ) {
            $urls[] = array(
                'loc'        => $this->rewrite_url( get_category_link( $cat->term_id ) ),
                'changefreq' => 'weekly',
                'priority'   => '0.6',
                'lastmod'    => gmdate('Y-m-d'),
            );
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        foreach ( $urls as $u ) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . esc_url( $u['loc'] ) . "</loc>\n";
            $xml .= "    <lastmod>" . esc_html( $u['lastmod'] ) . "</lastmod>\n";
            $xml .= "    <changefreq>" . esc_html( $u['changefreq'] ) . "</changefreq>\n";
            $xml .= "    <priority>" . esc_html( $u['priority'] ) . "</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        file_put_contents( $this->output_dir . '/sitemap.xml', $xml );
    }

    private function generate_robots() {
        $sitemap_url = $this->base_url . '/sitemap.xml';
        $content  = "User-agent: *\n";
        $content .= "Allow: /\n";
        $content .= "Disallow: /wp-admin/\n";
        $content .= "Disallow: /wp-login.php\n";
        $content .= "Disallow: /wp-json/\n";
        $content .= "\n";
        $content .= "Sitemap: $sitemap_url\n";

        file_put_contents( $this->output_dir . '/robots.txt', $content );
    }

    private function generate_404() {
        $site_name = esc_html( get_bloginfo('name') );
        $home_url  = esc_url( $this->base_url . '/' );

        $html  = "<!DOCTYPE html>\n";
        $html .= "<html lang=\"en\">\n";
        $html .= "<head>\n";
        $html .= "    <meta charset=\"UTF-8\">\n";
        $html .= "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
        $html .= "    <meta name=\"robots\" content=\"noindex, nofollow\">\n";
        $html .= "    <title>404 &ndash; Page Not Found | {$site_name}</title>\n";
        $html .= "    <style>\n";
        $html .= "        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }\n";
        $html .= "        body {\n";
        $html .= "            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;\n";
        $html .= "            background: #f8f9fa;\n";
        $html .= "            color: #333;\n";
        $html .= "            min-height: 100vh;\n";
        $html .= "            display: flex;\n";
        $html .= "            align-items: center;\n";
        $html .= "            justify-content: center;\n";
        $html .= "            padding: 2rem;\n";
        $html .= "        }\n";
        $html .= "        .container {\n";
        $html .= "            text-align: center;\n";
        $html .= "            max-width: 480px;\n";
        $html .= "        }\n";
        $html .= "        .code {\n";
        $html .= "            font-size: 7rem;\n";
        $html .= "            font-weight: 800;\n";
        $html .= "            color: #e0e0e0;\n";
        $html .= "            line-height: 1;\n";
        $html .= "            margin-bottom: 1rem;\n";
        $html .= "        }\n";
        $html .= "        h1 {\n";
        $html .= "            font-size: 1.75rem;\n";
        $html .= "            font-weight: 700;\n";
        $html .= "            margin-bottom: 0.75rem;\n";
        $html .= "        }\n";
        $html .= "        p {\n";
        $html .= "            color: #666;\n";
        $html .= "            margin-bottom: 2rem;\n";
        $html .= "            line-height: 1.6;\n";
        $html .= "        }\n";
        $html .= "        a.btn {\n";
        $html .= "            display: inline-block;\n";
        $html .= "            padding: 0.75rem 2rem;\n";
        $html .= "            background: #333;\n";
        $html .= "            color: #fff;\n";
        $html .= "            text-decoration: none;\n";
        $html .= "            border-radius: 6px;\n";
        $html .= "            font-weight: 600;\n";
        $html .= "            transition: background 0.2s;\n";
        $html .= "        }\n";
        $html .= "        a.btn:hover { background: #555; }\n";
        $html .= "    </style>\n";
        $html .= "</head>\n";
        $html .= "<body>\n";
        $html .= "    <div class=\"container\">\n";
        $html .= "        <div class=\"code\">404</div>\n";
        $html .= "        <h1>Page Not Found</h1>\n";
        $html .= "        <p>The page you&rsquo;re looking for doesn&rsquo;t exist or may have been moved.</p>\n";
        $html .= "        <a class=\"btn\" href=\"{$home_url}\">&larr; Back to Home</a>\n";
        $html .= "    </div>\n";
        $html .= "</body>\n";
        $html .= "</html>\n";

        file_put_contents( $this->output_dir . '/404.html', $html );
    }

    private function generate_htaccess() {
        $content  = "# Static Push — generated .htaccess\n\n";
        $content .= "Options -Indexes\n\n";
        $content .= "# Custom 404\n";
        $content .= "ErrorDocument 404 /404.html\n\n";
        $content .= "# Redirect trailing slashes\n";
        $content .= "RewriteEngine On\n";
        $content .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
        $content .= "RewriteCond %{REQUEST_FILENAME}/index.html -f\n";
        $content .= "RewriteRule ^(.*)$ /$1/index.html [L]\n\n";
        $content .= "# Security headers\n";
        $content .= "<IfModule mod_headers.c>\n";
        $content .= "    Header set X-Content-Type-Options \"nosniff\"\n";
        $content .= "    Header set X-Frame-Options \"SAMEORIGIN\"\n";
        $content .= "    Header set Referrer-Policy \"strict-origin-when-cross-origin\"\n";
        $content .= "</IfModule>\n\n";
        $content .= "# Cache static assets\n";
        $content .= "<IfModule mod_expires.c>\n";
        $content .= "    ExpiresActive On\n";
        $content .= "    ExpiresByType image/jpeg \"access plus 1 year\"\n";
        $content .= "    ExpiresByType image/png \"access plus 1 year\"\n";
        $content .= "    ExpiresByType image/webp \"access plus 1 year\"\n";
        $content .= "    ExpiresByType image/svg+xml \"access plus 1 year\"\n";
        $content .= "    ExpiresByType text/css \"access plus 1 month\"\n";
        $content .= "    ExpiresByType application/javascript \"access plus 1 month\"\n";
        $content .= "    ExpiresByType font/woff2 \"access plus 1 year\"\n";
        $content .= "</IfModule>\n";

        file_put_contents( $this->output_dir . '/.htaccess', $content );
    }

    private function rewrite_url( $url ) {
        return str_replace( get_site_url(), $this->base_url, $url );
    }
}
