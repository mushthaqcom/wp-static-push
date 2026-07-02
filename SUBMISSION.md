# WordPress.org Submission Info — Static Push

Dev reference only. Not shipped in the plugin package (excluded via `.distignore` / build step).

## Submit at
https://wordpress.org/plugins/developers/add/

## Form fields

**Plugin Name**
```
Static Push
```

**Requested plugin slug** (auto-derived from the name; confirm it reads)
```
static-push
```

**Plugin Description** (the box on the submission form — plain text, no markup)
```
Static Push crawls your live WordPress site, rewrites it into fully static HTML, CSS, JS, and asset files, and then either pushes it to a GitHub repository in a single atomic commit or hands it back to you as a downloadable ZIP. Because the output is plain static files, you can host it on GitHub Pages, Cloudflare Pages, Netlify, or any static host — fast, cheap, and with a much smaller attack surface than a live WordPress install. It also auto-generates sitemap.xml, robots.txt, a 404 page, and an .htaccess file, and analyzes your active plugins and theme for static compatibility. The only external service contacted is the GitHub API, and only when you click Push, using a Personal Access Token you provide.
```

**ZIP to upload**
```
~/Downloads/static-push.zip
```

## Header facts (from readme.txt / static-push.php)

| Field | Value |
|---|---|
| Version / Stable tag | 1.1.0 |
| Requires at least | WordPress 5.8 |
| Tested up to | 7.0 |
| Requires PHP | 7.4 |
| License | GPLv2 or later |
| Text Domain | static-push |
| Contributors | mushthaq  ← MUST match your real wordpress.org username |

## Pre-submission checklist

- [ ] `Contributors: mushthaq` in readme.txt is your actual wordpress.org username.
- [ ] Uploaded ZIP's top folder is `static-push/` and contains no dev files (.git, .claude, .distignore, README.md, CHANGELOG.md). The build in ~/Downloads already satisfies this.
- [ ] Plugin activates cleanly on a test site (run `php -l` on each file if you have PHP locally).
- [ ] wordpress.org account email is a real, monitored inbox (auto-replies/ticket systems are not allowed — the plugins team emails humans).
- [ ] Screenshots referenced in readme.txt (admin screen, site analysis) are added later to the SVN `/assets` folder — optional for submission, not part of the ZIP.

## What happens next

1. You upload the ZIP; the Plugins Team does a manual review (typically a few days to a couple of weeks).
2. On approval you get SVN access. Commit the plugin files to `/trunk`, then tag `/tags/1.1.0` matching the Stable tag.
3. Only commit release-ready code — avoid frequent trivial commits (guideline 14).
4. For each future release, bump the Version header AND the Stable tag together.

## Handy links to include in the reply if asked

- Development / source: https://github.com/mushthaq/... (your GitHub repo — satisfies the "public source access" guideline)
