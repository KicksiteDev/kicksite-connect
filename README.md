# kicksite-connect

WordPress Plugin For Kicksite Integrations and Connections

## Site identity marker

The plugin prints a hidden meta tag into the head of every front-end page:

```html
<meta name="kicksite-site-marker" content="kicksite">
```

Kicksite's website monitoring tool (separate repository) reads this tag to
answer one question: **is this domain still running the Kicksite plugin?**
An HTTP 200 alone cannot answer it — a customer who rebuilds their site on
another platform still returns 200.

The tag is not visible to visitors and contains no private information.

### Rules — this is a contract with another repository

1. **The tag name and content value never change.** Monitoring matches on
   them exactly. Changing either one silently breaks churn detection across
   every customer site.
2. **The marker is never gated on configuration.** It prints whether or not
   the Kicksite Integration Settings have been filled in. A site part-way
   through onboarding is still a Kicksite site — gating the marker would
   report every new build as a customer who left.
3. **Never put a visible credit in its place.** Anything a customer can see
   is something a customer can ask to have removed, which would break
   monitoring for that site with nobody noticing.
4. **If the tag ever must change**, the old one stays in place alongside the
   new one until monitoring has been updated and confirmed.

`tests/Unit/SiteMarkerTest.php` asserts the exact tag as a hard-coded literal
so any change to it fails loudly.

### Known limitation

Homepages are cached by Flywheel's Fastly layer. If a customer removes the
plugin but stays on our hosting, monitoring may keep seeing a cached page
carrying the marker for a few hours. This makes us slightly late to detect
that specific case, never wrong. It does not apply when a customer moves
hosting entirely, which is the main scenario the marker exists for.

## Tests

```bash
composer install        # first time only
vendor/bin/phpunit --testdox
```
