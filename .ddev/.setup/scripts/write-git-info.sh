#!/bin/bash
#ddev-generated
# If you want to take over this file and customize it, remove the line above
# and ddev will respect it and won't overwrite the file.
#
# Writes .Build/.git-info with the current branch, short commit and the
# project's DDEV sitename, so the intro page can show which checkout it is
# serving - useful when several git worktrees run in parallel. Runs on every
# container start (post-start hook). Fails open into empty branch/commit
# values so a missing git binary, a non-git directory, or a detached HEAD
# never blocks `ddev start`.

mkdir -p /var/www/html/.Build

branch=""
commit=""
if command -v git >/dev/null 2>&1 && git -C /var/www/html rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    branch=$(git -C /var/www/html rev-parse --abbrev-ref HEAD 2>/dev/null)
    commit=$(git -C /var/www/html rev-parse --short HEAD 2>/dev/null)
fi

{
    echo "branch=${branch}"
    echo "commit=${commit}"
    echo "dirname=${DDEV_SITENAME}"
} >/var/www/html/.Build/.git-info
