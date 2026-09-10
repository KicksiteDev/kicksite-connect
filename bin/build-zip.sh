#!/usr/bin/env bash
# Builds an installable kicksite-connect zip from a git ref.
#
# Usage:
#   bin/build-zip.sh [git-ref]            -> dist/kicksite-connect-1.2.0-abc1234.zip
#   bin/build-zip.sh --release [git-ref]  -> dist/kicksite-connect-1.2.0.zip
#
# --release drops the commit sha from the filename. Release assets get a
# predictable name because humans read them off the GitHub releases page, while
# local builds keep the sha so several builds of one version stay distinguishable.
set -euo pipefail

RELEASE=0
if [ "${1:-}" = "--release" ]; then
  RELEASE=1
  shift
fi

REF="${1:-HEAD}"
SLUG="kicksite-connect"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

# Read the version out of the ref being built rather than the working tree, so a
# dirty checkout cannot put the wrong version number on a release.
VERSION="$(git show "$REF:$SLUG.php" | sed -n 's/^ \* Version: *\(.*\)$/\1/p' | tr -d '[:space:]')"
SHA="$(git rev-parse --short "$REF")"

if [ -z "$VERSION" ]; then
  echo "Could not read a Version header from $REF:$SLUG.php" >&2
  exit 1
fi

if [ "$RELEASE" -eq 1 ]; then
  OUT="$ROOT/dist/$SLUG-$VERSION.zip"
else
  OUT="$ROOT/dist/$SLUG-$VERSION-$SHA.zip"
fi

mkdir -p "$ROOT/dist"
rm -f "$OUT"

# git archive respects export-ignore in .gitattributes and only ships
# committed files, so no local junk (.DS_Store, vendor, .phpunit cache) leaks in.
# The --prefix is what makes the zip unpack to wp-content/plugins/kicksite-connect
# rather than a directory named after the tag.
git archive --worktree-attributes --format=zip --prefix="$SLUG/" -o "$OUT" "$REF"

echo "Built: $OUT"
echo "Version: $VERSION  Ref: $REF ($SHA)"
echo
unzip -l "$OUT"
