# Releasing kicksite-connect

Every customer site running this plugin checks GitHub for a newer release twice
a day and installs it on its own. There is no approval step and no staging gate
between a tag and a customer's live site.

**Merging to `main` ships nothing. Pushing a tag deploys to every customer site.**

## Cutting a release

1. Bump **both** version numbers in `kicksite-connect.php` — they must match:

   ```php
    * Version:     1.2.0

   define( "KICKSITE_VERSION", "1.2.0" );
   ```

2. Commit and push to `main`.

3. Tag and push the tag:

   ```bash
   git tag v1.2.0
   git push origin v1.2.0
   ```

That's the release. CI runs the tests, checks the tag against the header, builds
the zip, and publishes it as a GitHub Release. Sites pick it up within about a
day; ManageWP can force it out sooner.

## Rules the tooling cannot enforce

### A version number is spent once

WordPress only ever installs a **higher** version. A site that has already taken
1.2.0 will never download 1.2.0 again, no matter what you do to the release.

So a broken release is never fixed in place. Delete it, re-tag it, force-push it
— none of that reaches the sites that already updated. **Ship 1.2.1.**

The same goes for rollbacks: to undo 1.2.0 you publish the old code as 1.2.1.
There is no way to move a site backwards.

### Do not tag to test something

A tag is a deploy. If you want to exercise the pipeline, do it against a staging
site with a real version bump you are willing to ship, not a throwaway tag.

### Bump the version in a PR, not directly before tagging

Version bumps get reviewed like anything else. It is the one line that decides
whether hundreds of sites act on the release.

## What CI already checks for you

You do not need to remember these — the build fails if you get them wrong:

- The tag matches the `Version:` header (`v1.2.0` ↔ `1.2.0`)
- `Version:` and `KICKSITE_VERSION` agree (unit test)
- The `Update URI` host matches the update filter's hook name (unit test)
- The built zip is attached to the release — the updater refuses a release
  without one rather than installing GitHub's auto-generated source archive

## If updates stop working

The failure mode is silent: sites simply never hear about new releases. Check in
this order:

1. Is the repo still public? Sites download anonymously, with no credentials.
2. Does the latest release have a `.zip` **asset** attached, not just source archives?
3. Does the tag match the `Version:` header in that tag's commit?
4. On a site: `wp transient delete update_plugins` then `wp plugin update kicksite-connect`.
