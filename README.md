# Lumea Glow Blocks

WPBakery elements + page templates for lumeaglow.com.au.

## Releasing an update
1. Make changes.
2. Bump the version in `lumea-glow-blocks.php` (both `Version:` and `LG_VER`).
3. Commit and push, then tag: `git tag v1.4.1 && git push origin v1.4.1`
4. GitHub Actions builds `lumea-glow-blocks.zip` and publishes the release.
5. On the site: Plugins → "Update now" (or it updates itself if auto-updates are on).
   To see it immediately: Plugins → Lumea Glow Blocks → "Check for updates".
