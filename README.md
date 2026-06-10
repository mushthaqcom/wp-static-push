# WP Static Push

Generate a fully static version of your WordPress site and push it to GitHub or download as a ZIP.

## Features

- 🔍 **Full Site Crawler** — crawls all posts, pages, categories, tags, and linked assets
- 🐙 **GitHub Push** — push directly to any repo/branch (perfect for GitHub Pages)
- 📦 **ZIP Download** — download static site as a ZIP for manual deployment
- 🔍 **SEO-Ready** — auto-generates `sitemap.xml`, `robots.txt`, `404.html`, and `.htaccess`
- 🧠 **Site Analysis** — analyzes your active plugins and theme for static compatibility
- ⚙️ **Configurable** — custom base URL, crawl depth, exclude paths, sub-directory push

## Installation

1. Upload the `wp-static-push` folder to `/wp-content/plugins/`
2. Activate the plugin in WordPress Admin → Plugins
3. Go to **Static Push** in the left admin menu

## GitHub Setup

1. Create a GitHub Personal Access Token at https://github.com/settings/tokens
   - Required scope: `repo` (for private repos) or `public_repo` (for public repos)
2. Create a repo (or use an existing one)
3. Enter your token and `owner/repo` in the plugin settings
4. Set branch to `gh-pages` for GitHub Pages deployment

## Deploy to GitHub Pages

1. Push to the `gh-pages` branch via the plugin
2. In your repo: Settings → Pages → Source: `gh-pages` branch → `/` (root)
3. Your site will be live at `https://username.github.io/repo`

## Limitations

| Feature | Static Compatible? |
|---|---|
| Blog posts & pages | ✅ Yes |
| Category/tag archives | ✅ Yes |
| Images, CSS, JS, fonts | ✅ Yes |
| Contact Form 7 / Gravity Forms | ⚠️ No — use Formspree or Basin |
| WooCommerce cart/checkout | ⚠️ No — dynamic only |
| WordPress search | ⚠️ No — use Pagefind or Lunr.js |
| Comments | ⚠️ No — use Disqus or Giscus |
| User login/registration | ⚠️ No |

## Requirements

- PHP 7.4+
- `ZipArchive` PHP extension (for ZIP download)
- WordPress 5.8+
- Outbound HTTP access (for self-crawling)

## Tips

- Disable caching plugins before generating (W3 Total Cache, WP Rocket, etc.) for fresh HTML output
- For large sites (200+ pages), increase `max_execution_time` in PHP settings
- The output is stored in `/wp-content/wp-static-push-output/site/` — protected from direct web access
