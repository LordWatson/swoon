#!/usr/bin/env bash
# Warn when module code changed without its module.md.
#
# Registered as a Stop hook in .claude/settings.json. Deliberately a WARNING,
# never a block: it exits 0 even when it finds something, so it can nag without
# getting in the way of a legitimate partial commit.
#
# Checks staged + unstaged changes for:
#   api/app/Modules/<Module>/...      -> needs api/app/Modules/<Module>/module.md
#   mobile/src/features/<module>/...  -> needs mobile/src/features/<module>/module.md
#
# Kept fast: two `git diff --name-only` calls and some string work. No file reads.

set -uo pipefail

# Not a git repo yet (or git missing) - nothing to compare against.
git rev-parse --git-dir >/dev/null 2>&1 || exit 0

changed=$(
  {
    git diff --name-only --diff-filter=ACMRD 2>/dev/null
    git diff --name-only --cached --diff-filter=ACMRD 2>/dev/null
  } | sort -u
)

[ -z "$changed" ] && exit 0

missing=""

# $1 = path prefix, $2 = human label
check_area() {
  local prefix="$1" label="$2" mods mod

  # Module names with a changed file that is NOT the module.md itself.
  mods=$(
    printf '%s\n' "$changed" \
      | grep -E "^${prefix}/[^/]+/" \
      | grep -v -E "^${prefix}/[^/]+/module\.md$" \
      | sed -E "s#^${prefix}/([^/]+)/.*#\1#" \
      | sort -u
  )

  for mod in $mods; do
    [ -z "$mod" ] && continue
    # Did this module's own module.md change too?
    if ! printf '%s\n' "$changed" | grep -qx "${prefix}/${mod}/module.md"; then
      missing="${missing}  - ${label} ${mod}  ->  ${prefix}/${mod}/module.md"$'\n'
    fi
  done
}

check_area "api/app/Modules" "backend module"
check_area "mobile/src/features" "mobile feature"

[ -z "$missing" ] && exit 0

cat <<EOF

────────────────────────────────────────────────────────────────────────
  📝 module.md not updated

  These modules have changed code but an untouched module.md:

${missing}
  Documentation is part of the definition of done (CLAUDE.md).
  Also remember: docs/MODULES.md status + a docs/SESSION_LOG.md entry.
────────────────────────────────────────────────────────────────────────

EOF

# Always succeed - this is a reminder, not a gate.
exit 0
