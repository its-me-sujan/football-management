#!/usr/bin/env bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

INTERVAL_SECONDS="${INTERVAL_SECONDS:-10}"
MODE="${1:-loop}"
HEARTBEAT_FILE="${HEARTBEAT_FILE:-scripts/.auto_commit_heartbeat.txt}"

SCOPES=(
  auth
  admin
  players
  coaches
  teams
  matches
  standings
  news
  profile
  ui
)

FEATURES=(
  login
  registration
  role-management
  player-tracking
  coach-dashboard
  team-workflow
  match-workflow
  standings-view
  news-module
  ui-polish
)

VERBS=(
  added
  improved
  enhanced
  implemented
)

random_from_array() {
  local -n items_ref="$1"
  local index=$((RANDOM % ${#items_ref[@]}))
  echo "${items_ref[$index]}"
}

build_commit_message() {
  local scope feature verb
  scope="$(random_from_array SCOPES)"
  feature="$(random_from_array FEATURES)"
  verb="$(random_from_array VERBS)"
  echo "feat(${scope}): ${verb} ${feature} functionality"
}

ensure_git_repo() {
  if ! git rev-parse --git-dir >/dev/null 2>&1; then
    echo "This script must run inside a git repository." >&2
    exit 1
  fi

  if ! git remote get-url origin >/dev/null 2>&1; then
    echo "Remote origin is not configured." >&2
    exit 1
  fi
  
}

  toggle_heartbeat_change() {
    mkdir -p "$(dirname "$HEARTBEAT_FILE")"

    if [[ ! -f "$HEARTBEAT_FILE" ]]; then
      printf 'auto-commit-heartbeat' > "$HEARTBEAT_FILE"
    fi

    if [[ -s "$HEARTBEAT_FILE" ]] && [[ "$(tail -c 1 "$HEARTBEAT_FILE" 2>/dev/null || true)" == $'\n' ]]; then
      # Remove trailing newline.
      local content
      content="$(cat "$HEARTBEAT_FILE")"
      printf '%s' "$content" > "$HEARTBEAT_FILE"
    else
      # Add trailing newline.
      printf '\n' >> "$HEARTBEAT_FILE"
    fi
  }

commit_once() {
  local branch message
  branch="$(git rev-parse --abbrev-ref HEAD)"

    toggle_heartbeat_change

  git add -A

  if git diff --cached --quiet; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] No changes to commit."
    return 0
  fi

  message="$(build_commit_message)"
  git commit -m "$message"
  git push origin "$branch"

  echo "[$(date '+%Y-%m-%d %H:%M:%S')] Pushed: $message"
}

run_loop() {
  echo "Starting auto commit loop with interval ${INTERVAL_SECONDS}s"
  while true; do
    if ! commit_once; then
      echo "[$(date '+%Y-%m-%d %H:%M:%S')] Commit/push failed. Retrying after interval." >&2
    fi
    sleep "$INTERVAL_SECONDS"
  done
}

ensure_git_repo

case "$MODE" in
  once)
    commit_once
    ;;
  loop)
    run_loop
    ;;
  *)
    echo "Usage: scripts/auto_commit_push.sh [once|loop]" >&2
    exit 1
    ;;
esac
