# kicksite-connect

WordPress plugin for Kicksite integrations and connections.

## Site identity marker

The plugin prints a hidden meta tag into the head of every front-end page.
Its name and content are a fixed contract with a separate Kicksite service:
**never rename it, change its value, or gate it on configuration.**

`tests/Unit/SiteMarkerTest.php` asserts the exact tag as a hard-coded literal,
so any change to it fails the suite loudly. If the tag genuinely must change,
the old one stays in place alongside the new one until the consuming service
has been updated and confirmed.

Background and rationale: see "Kicksite Connect — site identity marker" in
Nexus.

## Tests

```bash
composer install        # first time only
vendor/bin/phpunit --testdox
```
