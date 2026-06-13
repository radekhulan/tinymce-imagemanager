#!/usr/bin/env bash
#
# Builds (or updates) the "TinyMCE with modern File & Image Manager" bundle.
#
# Downloads and wires together, into this folder:
#   * TinyMCE 8 (community / GPL build) + all language packs
#   * the custom Lucide icon pack and skin overrides (from ./custom)
#   * Radek Hulan's File & Image Manager (prebuilt GitHub release — no build step)
#
# Re-run to update to the latest versions. Your uploads (fileimagemanager/public/media)
# and your edited config are preserved across updates.
#
# Usage:
#   ./setup.sh                                  # latest of everything
#   ./setup.sh --tinymce-version 8.6.0          # pin TinyMCE
#   ./setup.sh --fim-version v1.0.24            # pin File & Image Manager
#   ./setup.sh --skip-langs                     # no language packs
#
set -euo pipefail

TINYMCE_VERSION="latest"
FIM_VERSION="latest"
SKIP_LANGS=0

while [ $# -gt 0 ]; do
  case "$1" in
    --tinymce-version) TINYMCE_VERSION="$2"; shift 2 ;;
    --fim-version)     FIM_VERSION="$2";     shift 2 ;;
    --skip-langs)      SKIP_LANGS=1;         shift ;;
    -h|--help)
      grep '^#' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
    *) echo "Unknown option: $1" >&2; exit 1 ;;
  esac
done

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CUSTOM="$ROOT/custom"
TINYMCE_DIR="$ROOT/tinymce"
FIM_DIR="$ROOT/fileimagemanager"
UA="tinymce-imagemanager-setup"

cyan()  { printf '\n\033[36m==> %s\033[0m\n' "$1"; }
ok()    { printf '\033[32m    \xE2\x9C\x93 %s\033[0m\n' "$1"; }
note()  { printf '\033[90m    %s\033[0m\n' "$1"; }
warn()  { printf '\033[33m    ! %s\033[0m\n' "$1"; }
die()   { printf '\033[31mError: %s\033[0m\n' "$1" >&2; exit 1; }

# --- prerequisites ---------------------------------------------------------
for bin in curl unzip; do command -v "$bin" >/dev/null 2>&1 || die "'$bin' is required"; done
PHP_BIN="$(command -v php || true)"
[ -z "$PHP_BIN" ] && [ -x "c:/inetpub/php/php.exe" ] && PHP_BIN="c:/inetpub/php/php.exe"
[ -z "$PHP_BIN" ] && die "PHP not found on PATH"

for f in icons.js custom.css default-icons.min.js patch-config.php; do
  [ -f "$CUSTOM/$f" ] || die "Missing custom asset: custom/$f — is the repository intact?"
done

# curl with optional GitHub token for the API calls
gh_args=()
[ -n "${GITHUB_TOKEN:-}" ] && gh_args=(-H "Authorization: Bearer ${GITHUB_TOKEN}")

TMP="$(mktemp -d)"
cleanup() { rm -rf "$TMP"; }
trap cleanup EXIT

echo "TinyMCE with modern File & Image Manager — setup"
note "Target folder: $ROOT"

# ---------------------------------------------------------------------------
# 1) TinyMCE core
# ---------------------------------------------------------------------------
cyan "TinyMCE core"
if [ "$TINYMCE_VERSION" = "latest" ]; then
  TINYMCE_VERSION="$(curl -fsSL -A "$UA" https://registry.npmjs.org/tinymce/latest \
    | grep -o '"version":"[^"]*"' | head -1 | cut -d'"' -f4)"
  [ -n "$TINYMCE_VERSION" ] || die "Could not resolve latest TinyMCE version"
  note "Latest TinyMCE is $TINYMCE_VERSION"
fi
curl -fsSL -A "$UA" -o "$TMP/tinymce.zip" \
  "https://download.tiny.cloud/tinymce/community/tinymce_${TINYMCE_VERSION}.zip"
unzip -q "$TMP/tinymce.zip" -d "$TMP/tinymce"
SRC_TMCE="$TMP/tinymce/tinymce/js/tinymce"
[ -d "$SRC_TMCE" ] || die "Unexpected TinyMCE archive layout (no js/tinymce)"
rm -rf "$TINYMCE_DIR"
mkdir -p "$TINYMCE_DIR"
cp -a "$SRC_TMCE"/. "$TINYMCE_DIR"/
ok "TinyMCE $TINYMCE_VERSION installed to ./tinymce"

# ---------------------------------------------------------------------------
# 2) Language packs
# ---------------------------------------------------------------------------
if [ "$SKIP_LANGS" -eq 0 ]; then
  cyan "TinyMCE language packs"
  curl -fsSL -A "$UA" -o "$TMP/langs.zip" \
    "https://download.tiny.cloud/tinymce/community/languagepacks/8/langs.zip"
  unzip -q "$TMP/langs.zip" -d "$TMP/langs"
  mkdir -p "$TINYMCE_DIR/langs"
  cp -a "$TMP/langs/langs"/. "$TINYMCE_DIR/langs"/
  ok "$(find "$TINYMCE_DIR/langs" -name '*.js' | wc -l | tr -d ' ') language files installed"
else
  warn "Skipping language packs (--skip-langs)"
fi

# ---------------------------------------------------------------------------
# 3) Custom Lucide icons, skin overrides, empty default pack
# ---------------------------------------------------------------------------
cyan "Custom icons & skin"
mkdir -p "$TINYMCE_DIR/icons/lucide" "$TINYMCE_DIR/icons/default"
cp -f "$CUSTOM/icons.js" "$TINYMCE_DIR/icons/lucide/icons.js"
cp -f "$CUSTOM/icons.js" "$TINYMCE_DIR/icons/lucide/icons.min.js"
cp -f "$CUSTOM/custom.css" "$TINYMCE_DIR/custom.css"
cp -f "$CUSTOM/default-icons.min.js" "$TINYMCE_DIR/icons/default/icons.min.js"
ok "Lucide pack + custom.css installed; default pack neutralised"

# ---------------------------------------------------------------------------
# 4) File & Image Manager (prebuilt release — no composer/npm needed)
# ---------------------------------------------------------------------------
cyan "File & Image Manager"
if [ "$FIM_VERSION" = "latest" ]; then
  API="https://api.github.com/repos/radekhulan/fileimagemanager/releases/latest"
else
  API="https://api.github.com/repos/radekhulan/fileimagemanager/releases/tags/${FIM_VERSION}"
fi
REL_JSON="$(curl -fsSL -A "$UA" "${gh_args[@]}" "$API")"
FIM_TAG="$(printf '%s' "$REL_JSON" | grep -o '"tag_name": *"[^"]*"' | head -1 | cut -d'"' -f4)"
ASSET_URL="$(printf '%s' "$REL_JSON" | grep -o '"browser_download_url": *"[^"]*\.zip"' | head -1 | cut -d'"' -f4)"
[ -n "$ASSET_URL" ] || die "No .zip asset found on release ${FIM_TAG:-$FIM_VERSION}"
note "Release $FIM_TAG — $(basename "$ASSET_URL")"

curl -fsSL -A "$UA" "${gh_args[@]}" -o "$TMP/fim.zip" "$ASSET_URL"
unzip -q "$TMP/fim.zip" -d "$TMP/fim"
SRC_FIM="$TMP/fim/fileimagemanager"
[ -d "$SRC_FIM" ] || die "Unexpected release layout (no fileimagemanager/)"

# Preserve a user-edited config across updates.
CFG="$FIM_DIR/config/filemanager.php"
CFG_BACKUP=""
[ -f "$CFG" ] && CFG_BACKUP="$(cat "$CFG")"

# Drop stale code (hashed asset names accumulate). Media lives at the repo root, untouched.
if [ -d "$FIM_DIR" ]; then
  rm -rf "$FIM_DIR/src" "$FIM_DIR/vendor" "$FIM_DIR/lang" \
         "$FIM_DIR/public/assets" "$FIM_DIR/public/tinymce"
fi
mkdir -p "$FIM_DIR"
cp -a "$SRC_FIM"/. "$FIM_DIR"/

if [ -n "$CFG_BACKUP" ]; then
  printf '%s' "$CFG_BACKUP" > "$CFG"
  note "Preserved your existing config/filemanager.php"
fi
ok "File & Image Manager $FIM_TAG installed to ./fileimagemanager"

# ---------------------------------------------------------------------------
# 5) Patch config + media folders
# ---------------------------------------------------------------------------
cyan "Configuring File & Image Manager"
"$PHP_BIN" "$CUSTOM/patch-config.php" "$CFG" | while IFS= read -r line; do note "$line"; done

# Wire the manager's TinyMCE plugin into the editor as an internal plugin, so it loads via
# the `plugins` list at tinymce/plugins/fileimagemanager/plugin.min.js (required — otherwise
# TinyMCE can't find it and the editor falls back to a plain textarea).
mkdir -p "$TINYMCE_DIR/plugins/fileimagemanager"
cp -f "$FIM_DIR/public/tinymce/plugin.js" "$TINYMCE_DIR/plugins/fileimagemanager/plugin.min.js"

# Media is stored at the repository root (committed): ./media/source and ./media/thumbs
for d in media/source media/thumbs; do
  mkdir -p "$ROOT/$d"
  [ -f "$ROOT/$d/.gitkeep" ] || : > "$ROOT/$d/.gitkeep"
done
ok "Config patched; plugin wired into TinyMCE; media folders ready (./media)"

# ---------------------------------------------------------------------------
# 6) Version marker
# ---------------------------------------------------------------------------
printf '{\n  "tinymce": "%s",\n  "fileimagemanager": "%s",\n  "updated": "%s"\n}\n' \
  "$TINYMCE_VERSION" "$FIM_TAG" "$(date '+%Y-%m-%d %H:%M:%S')" > "$ROOT/.bundle-version.json"

# ---------------------------------------------------------------------------
# Done
# ---------------------------------------------------------------------------
URLBASE="/$(basename "$ROOT")"
case "$ROOT" in
  */wwwroot/*) URLBASE="/${ROOT#*/wwwroot/}" ;;
esac

printf '\n\033[32m========================================================================\033[0m\n'
printf '\033[32m Done. TinyMCE %s + File & Image Manager %s\033[0m\n' "$TINYMCE_VERSION" "$FIM_TAG"
printf '\033[32m========================================================================\033[0m\n'
echo  " Demo page :  http://localhost${URLBASE}/"
echo  " Manager   :  http://localhost${URLBASE}/fileimagemanager/public/"

printf '\n\033[33m------------------------------------------------------------------------\033[0m\n'
printf '\033[33m SECURITY WARNING\033[0m\n'
printf '\033[33m------------------------------------------------------------------------\033[0m\n'
echo  " The File & Image Manager ships with NO authentication so the demo works"
echo  " immediately. As installed, ANYONE who can reach the URL can upload,"
echo  " rename and DELETE files on your server."
echo  ""
echo  " Before production, lock it down — see the SECURITY section in README.md"
echo  " and the commented gate at the top of:"
echo  "   fileimagemanager/config/filemanager.php"
printf '\033[33m------------------------------------------------------------------------\033[0m\n\n'
