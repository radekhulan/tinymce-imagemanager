#!/usr/bin/env bash
#
# Runs the demo page (index.php) and the File & Image Manager in PHP's built-in web server.
# router.php routes the demo and the manager just like IIS/Apache would.
#
# By default it listens on every interface (0.0.0.0) and prints both the localhost URL and
# your machine's LAN IP, so you can reach the project from another device on the network.
#
# Usage:
#   ./dev-server.sh                 # http://localhost:8080/  + network URL
#   ./dev-server.sh --port 9000
#   ./dev-server.sh --local-only    # localhost only (not on the network)
#   ./dev-server.sh --no-browser
#
set -euo pipefail

PORT=8080
LOCAL_ONLY=0
OPEN=1

while [ $# -gt 0 ]; do
  case "$1" in
    --port)        PORT="$2"; shift 2 ;;
    --local-only)  LOCAL_ONLY=1; shift ;;
    --no-browser)  OPEN=0; shift ;;
    -h|--help)     grep '^#' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
    *) echo "Unknown option: $1" >&2; exit 1 ;;
  esac
done

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PHP_BIN="$(command -v php || true)"
[ -z "$PHP_BIN" ] && [ -x "c:/inetpub/php/php.exe" ] && PHP_BIN="c:/inetpub/php/php.exe"
[ -z "$PHP_BIN" ] && { echo "PHP not found on PATH" >&2; exit 1; }

[ -f "$ROOT/tinymce/tinymce.min.js" ] || \
  echo "Note: the bundle isn't assembled yet — run ./setup.sh first."

lan_ips() {
  # Try the common tools in order; print one IPv4 per line.
  if command -v hostname >/dev/null 2>&1 && hostname -I >/dev/null 2>&1; then
    hostname -I | tr ' ' '\n' | grep -E '^[0-9]+\.' | grep -vE '^127\.|^169\.254\.'
  elif command -v ip >/dev/null 2>&1; then
    ip -4 -o addr show scope global | awk '{print $4}' | cut -d/ -f1
  elif command -v ifconfig >/dev/null 2>&1; then
    ifconfig | awk '/inet /{print $2}' | sed 's/addr://' | grep -vE '^127\.|^169\.254\.'
  fi
}

if [ "$LOCAL_ONLY" -eq 1 ]; then BIND=127.0.0.1; else BIND=0.0.0.0; fi
LOCAL_URL="http://localhost:${PORT}/"

printf '\nPHP dev server\n'
echo "  root    : $ROOT"
printf '  \033[36mLocal   : %s\033[0m\n' "$LOCAL_URL"
if [ "$LOCAL_ONLY" -eq 0 ]; then
  found=0
  while IFS= read -r ip; do
    [ -n "$ip" ] || continue
    printf '  \033[32mNetwork : http://%s:%s/\033[0m\n' "$ip" "$PORT"
    found=1
  done < <(lan_ips | sort -u)
  [ "$found" -eq 0 ] && echo "  Network : (no LAN IPv4 detected)"
  echo  "  manager : http://localhost:${PORT}/wysiwyg/fileimagemanager/public/"
  printf '  \033[33m! Reachable on your network — the file manager has no auth. Use --local-only to restrict.\033[0m\n'
else
  echo "  manager : ${LOCAL_URL}wysiwyg/fileimagemanager/public/"
fi
echo "  (press Ctrl+C to stop)"
echo

if [ "$OPEN" -eq 1 ]; then
  ( sleep 1
    if   command -v xdg-open >/dev/null 2>&1; then xdg-open "$LOCAL_URL"
    elif command -v open      >/dev/null 2>&1; then open "$LOCAL_URL"
    elif command -v start     >/dev/null 2>&1; then start "$LOCAL_URL"
    fi ) >/dev/null 2>&1 &
fi

exec "$PHP_BIN" -S "${BIND}:${PORT}" -t "$ROOT" "$ROOT/router.php"
