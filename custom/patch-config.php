<?php

/**
 * patch-config.php — invoked by setup.ps1 / setup.sh.
 *
 * Takes the File & Image Manager config file (config/filemanager.php) that ships
 * inside the downloaded release and rewrites it for this self-contained bundle:
 *
 *   1. Media is stored INSIDE the manager's own public/ directory
 *      (fileimagemanager/public/media/{source,thumbs}) instead of at the web-server
 *      DOCUMENT_ROOT, so the whole thing is portable and does not pollute the site root.
 *      URLs are derived at runtime from SCRIPT_NAME, so it works under any mount path.
 *
 *   2. A clearly-labelled — but DISABLED — session security gate is injected at the top
 *      so it is obvious where and how to lock the manager down before production.
 *
 * The script only rewrites four lines and (optionally) prepends a comment block; every
 * other setting the release ships is left untouched. It is idempotent: running it again
 * produces the same file.
 *
 * Usage:  php patch-config.php <path-to-config/filemanager.php>
 */

declare(strict_types=1);

$file = $argv[1] ?? null;
if ($file === null || !is_file($file)) {
    fwrite(STDERR, "patch-config: config file not found: " . var_export($file, true) . "\n");
    exit(1);
}

$src = file_get_contents($file);
if ($src === false) {
    fwrite(STDERR, "patch-config: cannot read $file\n");
    exit(1);
}

/*
 * 1) Media paths — stored at the REPOSITORY ROOT in media/{source,thumbs} so they can be
 *    committed alongside the project.
 *    - current_path / thumbs_base_path : filesystem. The config lives at
 *      <root>/wysiwyg/fileimagemanager/config/, so dirname(__DIR__, 3) is the repo root.
 *    - upload_dir / thumbs_upload_dir   : public URL, derived from SCRIPT_NAME at runtime.
 *      The manager is served from <root>/wysiwyg/fileimagemanager/public, so we strip that
 *      suffix to get the repo-root URL.
 *
 * In the produced PHP, str_replace('\\', '/', ...) normalises Windows separators in the
 * script path; '\\' in a single-quoted PHP string is a single backslash.
 */
$urlExpr = "preg_replace('#/wysiwyg/fileimagemanager/public\$#', '', rtrim(str_replace('\\\\', '/', dirname(\$_SERVER['SCRIPT_NAME'] ?? '/')), '/'))";

$replacements = [
    'upload_dir'        => $urlExpr . " . '/media/source/'",
    'thumbs_upload_dir' => $urlExpr . " . '/media/thumbs/'",
    'current_path'      => "dirname(__DIR__, 3) . '/media/source/'",
    'thumbs_base_path'  => "dirname(__DIR__, 3) . '/media/thumbs/'",
];

$changed = [];
foreach ($replacements as $key => $value) {
    $pattern = '/^([ \t]*)\'' . preg_quote($key, '/') . '\'\s*=>.*$/m';
    $src = preg_replace_callback(
        $pattern,
        static function (array $m) use ($key, $value): string {
            return $m[1] . "'$key' => $value,";
        },
        $src,
        1,
        $count
    );
    if ($count > 0) {
        $changed[] = $key;
    }
}

/*
 * 2) Security gate — injected once, DISABLED. The bundle ships open so the demo works
 *    out of the box; this comment block shows exactly how to protect it. We only add it
 *    if the config does not already mention ImageEditorAllowed (so we never clobber a
 *    gate the user has deliberately enabled).
 */
if (strpos($src, 'ImageEditorAllowed') === false) {
    $gate = <<<'PHP'

/* ==========================================================================
 *  SECURITY GATE — DISABLED for the demo.  ENABLE THIS BEFORE PRODUCTION!
 *  Without it, anyone who can reach this URL can upload / rename / DELETE files.
 *  Log the user in elsewhere, set the session flag, then uncomment the block:
 * ========================================================================== */
// if (session_status() === PHP_SESSION_NONE) {
//     // session_name('YOURAPP');   // share the session cookie with your admin/login
//     session_start();
// }
// if (empty($_SESSION['ImageEditorAllowed'])) {
//     http_response_code(403);
//     exit('Access denied');
// }

PHP;
    $src = preg_replace('/^<\?php\s*/', "<?php\n" . $gate . "\n", $src, 1);
    $changed[] = 'security-gate (injected, disabled)';
}

if (file_put_contents($file, $src) === false) {
    fwrite(STDERR, "patch-config: cannot write $file\n");
    exit(1);
}

fwrite(STDOUT, "patch-config: updated [" . implode(', ', $changed ?: ['nothing — already patched']) . "]\n");
exit(0);
