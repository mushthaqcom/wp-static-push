=== Static Push ===
Contributors: mushthaq
Tags: static site, github, deploy, sitemap, static export
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate a fully static version of your WordPress site and push it to GitHub or download it as a ZIP. SEO-friendly, with sitemap, robots.txt, and a 404 page.

== Description ==

Static Push crawls your live WordPress site, rewrites it into a fully static set of HTML, CSS, JS, and asset files, and then either pushes it to a GitHub repository or hands it back to you as a downloadable ZIP.

Because the output is plain static files, you can host it on GitHub Pages, Cloudflare Pages, Netlify, or any static host — fast, cheap, and with a much smaller attack surface than a live WordPress install.

= Features =

* **Full site crawler** — crawls all posts, pages, categories, tags, and linked assets.
* **Atomic GitHub push** — every push lands in a single git commit using the GitHub Git Tree API, so hosts like Cloudflare Pages trigger exactly one deployment per push, not one per file.
* **ZIP download** — download the static site as a ZIP for manual deployment anywhere.
* **SEO-ready** — auto-generates `sitemap.xml`, `robots.txt`, `404.html`, and an `.htaccess` file.
* **Site analysis** — analyzes your active plugins and theme for static compatibility and flags anything dynamic.
* **Detailed logs** — a collapsible log panel after every operation, with error and warning highlighting.
* **Configurable** — custom production base URL, crawl depth, exclude paths, and an optional sub-directory for the pushed files.

= What does not work in a static site =

Static output cannot run server-side code. The following need third-party or client-side replacements:

* Dynamic forms (Contact Form 7, Gravity Forms, WooCommerce checkout) — use a form service such as Formspree or Basin.
* WordPress search — use a client-side search such as Pagefind or Lunr.js.
* Comments — use Disqus or Giscus.
* User login and registration.

== Installation ==

1. Upload the `static-push` folder to `/wp-content/plugins/`, or install it from the Plugins screen in your WordPress admin.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Open **Static Push** in the left admin menu.

= GitHub setup =

1. Create a GitHub Personal Access Token at https://github.com/settings/tokens with the `repo` scope (or `public_repo` for public repositories only).
2. Enter your token and your `owner/repo` in the plugin settings.
3. Set the target branch (for example `gh-pages`) and save.
4. Click **Generate Static Site**, then **Push to GitHub**.

== Frequently Asked Questions ==

= Where is the generated site stored? =

In `/wp-content/static-push-output/site/`. That directory is protected from direct web access.

= Why does my push create exactly one commit? =

Static Push uses the GitHub Git Tree API to bundle every changed file into a single commit. This means static hosts that deploy on push (GitHub Pages, Cloudflare Pages) run only one deployment per push.

= My site is large and generation times out. What can I do? =

Increase `max_execution_time` in your PHP settings, and lower the crawl depth or add exclude paths for sections you do not need in the static output.

= Should I disable caching plugins first? =

Yes. Disable caching plugins (W3 Total Cache, WP Rocket, LiteSpeed Cache, etc.) before generating so the crawler captures fresh HTML.

== Screenshots ==

1. The Static Push admin screen with generate, push, and download actions.
2. Site analysis showing plugin and theme static-compatibility hints.

== Changelog ==

= 1.1.0 =
* Changed: GitHub push is now atomic — all files are committed in a single commit using the GitHub Git Tree API, so only one deployment is triggered per push.
* Added: Collapsible detailed log panel after Generate and Push, with error and warning highlighting.
* Added: Status cycling messages and elapsed-time reporting for crawl and push.
* Added: Target branch is auto-created on first push if it does not exist.
* Fixed: Multiple deployments triggered by a single push (previously caused by per-file commits).

= 1.0.0 =
* Initial release.
* Full site crawler, GitHub push, ZIP download, SEO file generation, and site analysis.

== Upgrade Notice ==

= 1.1.0 =
Pushes are now atomic (one commit per push), so static hosts trigger a single deployment. Adds a detailed log panel.
