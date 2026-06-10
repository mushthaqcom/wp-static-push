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
            'lastmod'    => date('Y-m-d'),
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
                'lastmod'    => date( 'Y-m-d', strtotime( $post->post_modified ) ),
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
                'lastmod'    => date( 'Y-m-d', strtotime( $page->post_modified ) ),
            );
        }

        // Categories
        $categories = get_categories( array( 'hide_empty' => true ) );
        foreach ( $categories as $cat ) {
            $urls[] = array(
                'loc'        => $this->rewrite_url( get_category_link( $cat->term_id ) ),
                'changefreq' => 'weekly',
                'priority'   => '0.6',
                'lastmod'    => date('Y-m-d'),
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
        $site_name = get_bloginfo('name');
        $home_url  = $this->base_url . '/';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>404 – Page Not Found | {$site_name}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            color: #333;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .container {
            text-align: center;
            max-width: 480px;
        }
        .code {
            font-size: 7rem;
            font-weight: 800;
            color: #e0e0e0;
            line-height: 1;
            margin-bottom: 1rem;
        }
        h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        p {
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        a.btn {
            display: inline-block;
            padding: 0.75rem 2rem;
            background: #333;
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: background 0.2s;
        }
        a.btn:hover { background: #555; }
    </style>
</head>
<body>
    <div class="container">
        <div class="code">404</div>
        <h1>Page Not Found</h1>
        <p>The page you're looking for doesn't exist or may have been moved.</p>
        <a class="btn" href="{$home_url}">← Back to Home</a>
    </div>
</body>
</html>
HTML;

        file_put_contents( $this->output_dir . '/404.html', $html );
    }

    private function generate_htaccess() {
        $content  = "# WP Static Push — generated .htaccess\n\n";
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
