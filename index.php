<?php

/**
 * Demo / quick-start page for "TinyMCE with modern File & Image Manager".
 *
 * Pure showcase — no auth, no state. Detects whether setup has run and adapts.
 * Safe to delete in your own deployment.
 */

declare(strict_types=1);

$root = __DIR__;

// URL path under which THIS page is served, e.g. /tinymce-imagemanager
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($base === '.' || $base === '/') {
    $base = '';
}

$tinymceJs   = $base . '/wysiwyg/tinymce/tinymce.min.js';
$managerUrl  = $base . '/wysiwyg/fileimagemanager/public/';
$pluginUrl   = $base . '/wysiwyg/fileimagemanager/public/tinymce/plugin.js';

$installed = is_file($root . '/wysiwyg/tinymce/tinymce.min.js')
    && is_file($root . '/wysiwyg/fileimagemanager/public/index.php');

// Versions (written by setup)
$ver = ['tinymce' => null, 'fileimagemanager' => null, 'updated' => null];
if (is_file($root . '/.bundle-version.json')) {
    $j = json_decode((string) file_get_contents($root . '/.bundle-version.json'), true);
    if (is_array($j)) {
        $ver = array_merge($ver, $j);
    }
}

// Languages available for the live demo, built from installed packs.
$langNames = [
    'cs' => 'Čeština', 'sk' => 'Slovenčina', 'de' => 'Deutsch', 'hr' => 'Hrvatski',
    'hu_HU' => 'Magyar', 'it' => 'Italiano', 'sl_SI' => 'Slovenščina', 'pl' => 'Polski',
    'fr_FR' => 'Français', 'es' => 'Español', 'nl' => 'Nederlands', 'pt_PT' => 'Português',
    'pt_BR' => 'Português (BR)', 'ru' => 'Русский', 'uk' => 'Українська', 'tr' => 'Türkçe',
    'ja' => '日本語', 'zh_CN' => '中文', 'ar' => 'العربية', 'sv_SE' => 'Svenska',
    'da' => 'Dansk', 'fi' => 'Suomi', 'el' => 'Ελληνικά', 'ro' => 'Română',
];
$langs = ['' => 'English (default)'];
foreach (glob($root . '/wysiwyg/tinymce/langs/*.js') ?: [] as $f) {
    $code = basename($f, '.js');
    $langs[$code] = $langNames[$code] ?? $code;
}

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// The init snippet shown in the "Quick use" panel (kept in sync with the live editor below).
$snippet = <<<JS
<script src="{$tinymceJs}"></script>
<script>
tinymce.init({
  selector: 'textarea.wysiwyg',
  license_key: 'gpl',
  skin: 'oxide',
  icons: 'lucide',                 // custom Lucide icon pack
  branding: false,
  promotion: false,
  menubar: false,
  toolbar_mode: 'floating',
  fileimagemanager_url: '{$managerUrl}',
  fileimagemanager_dragdrop: true,   // drop images straight onto the editor (default: true)
  plugins: 'quickbars autoresize anchor advlist autolink table code link lists image media fileimagemanager fullscreen visualblocks searchreplace',
  toolbar1: 'blocks | bold italic underline strikethrough removeformat | bullist numlist | alignleft aligncenter alignright | link unlink | blockquote',
  toolbar2: 'undo redo | table hr | image media fileimagemanager | searchreplace visualblocks | fullscreen code'
});
</script>
JS;

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>TinyMCE with integrated modern File &amp; Image Manager</title>
<style>
  :root{
    --bg:#eef2f7; --panel:#ffffff; --ink:#0f172a; --muted:#64748b; --line:#e2e8f0;
    --brand:#4f46e5; --brand2:#2563eb; --accent:#0ea5e9; --amber:#b45309; --amber-bg:#fffbeb; --amber-line:#fde68a;
    --radius:14px; --shadow:0 1px 2px rgba(15,23,42,.05), 0 10px 30px rgba(15,23,42,.06);
  }
  *{box-sizing:border-box}
  html,body{margin:0}
  body{
    font:15px/1.55 system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
    color:var(--ink);
    background:
      radial-gradient(1200px 480px at 80% -10%, #e0e7ff 0, transparent 60%),
      radial-gradient(900px 420px at 0% 0%, #cffafe 0, transparent 55%),
      var(--bg);
    min-height:100vh;
  }
  a{color:var(--brand2);text-decoration:none}
  a:hover{text-decoration:underline}
  .wrap{max-width:1480px;margin:0 auto;padding:28px 22px 64px}

  header.top{display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:22px}
  .logo{width:46px;height:46px;border-radius:12px;flex:0 0 auto;
    background:linear-gradient(135deg,var(--brand),var(--accent));
    display:grid;place-items:center;box-shadow:var(--shadow)}
  .logo svg{width:26px;height:26px;color:#fff}
  .titles h1{margin:0;font-size:21px;letter-spacing:-.01em}
  .titles p{margin:2px 0 0;color:var(--muted);font-size:13.5px}
  .top .spacer{flex:1}
  .top nav{display:flex;gap:8px;flex-wrap:wrap}
  .pill{display:inline-flex;align-items:center;gap:7px;height:32px;padding:0 12px;border-radius:999px;
    background:var(--panel);border:1px solid var(--line);font-size:12.5px;color:#334155;box-shadow:var(--shadow)}
  .pill b{color:var(--ink)}
  .pill .dot{width:7px;height:7px;border-radius:50%;background:#22c55e}

  .banner{display:flex;gap:12px;align-items:flex-start;background:var(--amber-bg);
    border:1px solid var(--amber-line);color:#7c2d12;border-radius:var(--radius);padding:13px 15px;margin-bottom:22px}
  .banner svg{flex:0 0 auto;width:20px;height:20px;color:var(--amber);margin-top:1px}
  .banner b{color:#7c2d12}
  .banner code{background:#fff7ed;border:1px solid var(--amber-line);border-radius:6px;padding:1px 6px;font-size:12.5px}

  .grid{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:22px;align-items:start}
  @media(max-width:980px){.grid{grid-template-columns:1fr}}

  .card{background:var(--panel);border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow)}
  .card .hd{padding:14px 18px;border-bottom:1px solid var(--line);display:flex;align-items:center;gap:10px}
  .card .hd h2{margin:0;font-size:15px}
  .card .hd .tag{margin-left:auto;font-size:12px;color:var(--muted)}
  .card .bd{padding:18px}

  textarea.wysiwyg{width:100%;min-height:360px}

  .editor-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
  .btn{appearance:none;cursor:pointer;border:1px solid var(--line);background:#fff;color:#1e293b;
    height:38px;padding:0 15px;border-radius:9px;font-size:14px;font-weight:550;display:inline-flex;align-items:center;gap:8px;
    transition:background .12s,border-color .12s,transform .04s}
  .btn:hover{background:#f1f5f9}
  .btn:active{transform:translateY(1px)}
  .btn svg{width:16px;height:16px}
  .btn.primary{background:linear-gradient(135deg,var(--brand),var(--brand2));border-color:transparent;color:#fff}
  .btn.primary:hover{filter:brightness(1.05);background:linear-gradient(135deg,var(--brand),var(--brand2))}

  .side .card+.card{margin-top:18px}
  .card.quickuse{grid-column:1 / -1}
  .card.quickuse .snippet{max-height:none}
  .field{margin-bottom:6px}
  label.lbl{display:block;font-size:12.5px;color:var(--muted);margin-bottom:6px;font-weight:600;letter-spacing:.02em;text-transform:uppercase}
  select{width:100%;height:38px;border:1px solid var(--line);border-radius:9px;padding:0 10px;font-size:14px;background:#fff;color:#1e293b}

  pre.snippet{margin:0;background:#0f172a;color:#e2e8f0;border-radius:10px;padding:14px;overflow:auto;
    font:12.5px/1.55 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;max-height:340px}
  .snip-wrap{position:relative}
  .snip-wrap .copy{position:absolute;top:8px;right:8px}

  ul.facts{list-style:none;margin:0;padding:0}
  ul.facts li{display:flex;gap:10px;padding:7px 0;border-bottom:1px solid #f1f5f9;font-size:13.5px}
  ul.facts li:last-child{border-bottom:0}
  ul.facts li svg{width:16px;height:16px;color:#16a34a;flex:0 0 auto;margin-top:2px}
  .muted{color:var(--muted)}

  .setup-steps{counter-reset:s;list-style:none;margin:0;padding:0}
  .setup-steps li{counter-increment:s;position:relative;padding:8px 0 8px 38px;border-bottom:1px solid #f1f5f9}
  .setup-steps li:last-child{border-bottom:0}
  .setup-steps li::before{content:counter(s);position:absolute;left:0;top:8px;width:26px;height:26px;border-radius:50%;
    background:var(--brand);color:#fff;display:grid;place-items:center;font-size:13px;font-weight:700}
  code.k{background:#f1f5f9;border:1px solid var(--line);border-radius:6px;padding:1px 7px;
    font:12.5px ui-monospace,Consolas,monospace}

  dialog#html{border:1px solid var(--line);border-radius:var(--radius);max-width:760px;width:92%;padding:0;box-shadow:0 30px 60px rgba(15,23,42,.3)}
  dialog#html::backdrop{background:rgba(15,23,42,.45)}
  dialog#html .hd{display:flex;align-items:center;padding:14px 18px;border-bottom:1px solid var(--line)}
  dialog#html .hd h3{margin:0;font-size:15px}
  dialog#html pre{margin:0;padding:16px;max-height:60vh;overflow:auto;font:12.5px/1.5 ui-monospace,Consolas,monospace;background:#f8fafc}

  /* docs / info sections */
  .section{margin-top:42px}
  .section .shd{display:flex;align-items:center;gap:14px;margin:0 0 18px}
  .section .shd h2{margin:0;font-size:18px;letter-spacing:-.01em;white-space:nowrap}
  .section .shd .ln{flex:1;height:1px;background:linear-gradient(90deg,var(--line),transparent)}
  .cols{display:grid;grid-template-columns:repeat(auto-fit,minmax(248px,1fr));gap:16px}
  .feat{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:16px 17px;box-shadow:var(--shadow)}
  .feat h3{margin:0 0 6px;font-size:14.5px;display:flex;align-items:center;gap:9px}
  .feat h3 svg{width:18px;height:18px;color:var(--brand);flex:0 0 auto}
  .feat p{margin:0;color:var(--muted);font-size:13px}
  .panelcard{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:6px 18px;box-shadow:var(--shadow)}
  .kv{width:100%;border-collapse:collapse;font-size:13.5px}
  .kv th,.kv td{text-align:left;padding:10px 6px;border-bottom:1px solid var(--line);vertical-align:top}
  .kv th{width:210px;color:#334155;font-weight:600;white-space:nowrap}
  .kv td code,code.inl{background:#f1f5f9;border:1px solid var(--line);border-radius:6px;padding:1px 6px;font:12.5px ui-monospace,Consolas,monospace}
  .kv tr:last-child th,.kv tr:last-child td{border-bottom:0}
  .twocol{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  @media(max-width:760px){.twocol{grid-template-columns:1fr}}
  .block{background:var(--panel);border:1px solid var(--line);border-radius:12px;padding:17px 18px;box-shadow:var(--shadow)}
  .block.warnbox{background:var(--amber-bg);border-color:var(--amber-line)}
  .block h3{margin:0 0 9px;font-size:15px;display:flex;align-items:center;gap:8px}
  .block p{margin:0 0 9px;font-size:13.5px;color:#334155}
  .block ul{margin:0;padding-left:18px;font-size:13.5px;color:#334155}
  .block ul li{margin:4px 0}
  .block pre{margin:9px 0 0;background:#0f172a;color:#e2e8f0;border-radius:9px;padding:12px;overflow:auto;
    font:12.5px/1.5 ui-monospace,Consolas,monospace}
  .badge{display:inline-block;font-size:11px;font-weight:700;letter-spacing:.03em;padding:2px 9px;border-radius:999px;vertical-align:middle}
  .badge.gpl{background:#eef2ff;color:#4338ca;border:1px solid #c7d2fe}
  .badge.cc0{background:#ecfdf5;color:#047857;border:1px solid #a7f3d0}
  footer{margin-top:40px;color:var(--muted);font-size:12.5px;text-align:center}

  /* Dark editor preview — toggled via <html class="tox-dark">. Overrides custom.css
     (higher specificity wins). Lucide icons follow `color` through currentColor,
     so flipping the text colour recolours the icons automatically. */
  html.tox-dark .tox.tox-tinymce{border-color:#334155 !important;box-shadow:0 1px 2px rgba(0,0,0,.4) !important}
  html.tox-dark .tox .tox-editor-header,
  html.tox-dark .tox .tox-toolbar,
  html.tox-dark .tox .tox-toolbar__primary,
  html.tox-dark .tox .tox-toolbar-overlord{background:#1e293b !important;border-bottom-color:#334155 !important}
  html.tox-dark .tox .tox-toolbar__group{border-right-color:#27374a !important}
  html.tox-dark .tox .tox-tbtn{color:#e2e8f0 !important}
  html.tox-dark .tox .tox-tbtn:hover{background:#334155 !important;color:#fff !important}
  html.tox-dark .tox .tox-tbtn--enabled,
  html.tox-dark .tox .tox-tbtn--enabled:hover{background:#1e3a8a !important;color:#93c5fd !important}
  html.tox-dark .tox .tox-tbtn__select-label,
  html.tox-dark .tox .tox-listbox__select-label,
  html.tox-dark .tox .tox-tbtn__select-chevron svg{color:#e2e8f0 !important}
  html.tox-dark .tox .tox-split-button:hover{background:#334155 !important}
  html.tox-dark .tox .tox-statusbar{background:#1e293b !important;border-top-color:#334155 !important;color:#94a3b8 !important}
  html.tox-dark .tox .tox-statusbar a,
  html.tox-dark .tox .tox-statusbar__path-item{color:#cbd5e1 !important}
  html.tox-dark .tox .tox-menu,
  html.tox-dark .tox .tox-collection--list,
  html.tox-dark .tox .tox-dialog,
  html.tox-dark .tox .tox-dialog__header,
  html.tox-dark .tox .tox-dialog__footer,
  html.tox-dark .tox .tox-toolbar__overflow,
  html.tox-dark .tox .tox-pop__dialog{background:#1e293b !important;border-color:#334155 !important;color:#e2e8f0 !important}
  html.tox-dark .tox .tox-collection__item{color:#e2e8f0 !important}
  html.tox-dark .tox .tox-collection__item--active:not(.tox-collection__item--state-disabled){background:#334155 !important;color:#fff !important}
  html.tox-dark .tox .tox-collection__item--enabled{color:#93c5fd !important}
  html.tox-dark .tox .tox-collection__group{border-bottom-color:#27374a !important}
  html.tox-dark .tox .tox-textfield,
  html.tox-dark .tox .tox-textarea,
  html.tox-dark .tox .tox-listbox,
  html.tox-dark .tox .tox-toolbar-textfield{background:#0f172a !important;border-color:#334155 !important;color:#e2e8f0 !important}
  html.tox-dark .tox .tox-label,
  html.tox-dark .tox .tox-toolbar-label,
  html.tox-dark .tox .tox-dialog__title{color:#cbd5e1 !important}
  html.tox-dark .tox .tox-button--naked.tox-button--icon,
  html.tox-dark .tox .tox-browse-url{color:#e2e8f0 !important}
  html.tox-dark .tox .tox-button--naked.tox-button--icon:hover,
  html.tox-dark .tox .tox-browse-url:hover{background:#334155 !important;color:#fff !important}
  html.tox-dark .tox .tox-button--icon .tox-icon svg,
  html.tox-dark .tox .tox-button.tox-button--icon .tox-icon svg{fill:#e2e8f0 !important}
  footer a{color:var(--muted)}
</style>
</head>
<body>
<div class="wrap">

  <header class="top">
    <div class="logo" aria-hidden="true">
      <!-- lucide: image-plus -->
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h7"/><path d="M16 5h6"/><path d="M19 2v6"/>
        <circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/>
      </svg>
    </div>
    <div class="titles">
      <h1>TinyMCE with integrated modern File &amp; Image Manager</h1>
      <p>Self-contained WYSIWYG bundle — Lucide icons, custom skin, drag-and-drop media browser.</p>
    </div>
    <div class="spacer"></div>
    <nav>
      <?php if ($ver['tinymce']): ?>
        <span class="pill"><span class="dot"></span>TinyMCE&nbsp;<b><?= e((string) $ver['tinymce']) ?></b></span>
      <?php endif; ?>
      <?php if ($ver['fileimagemanager']): ?>
        <span class="pill"><span class="dot"></span>Manager&nbsp;<b><?= e((string) $ver['fileimagemanager']) ?></b></span>
      <?php endif; ?>
      <a class="pill" href="README.md">README</a>
      <a class="pill" href="https://github.com/radekhulan/fileimagemanager" target="_blank" rel="noopener">GitHub ↗</a>
    </nav>
  </header>

  <div class="banner">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/>
    </svg>
    <div><b>Security:</b> the file manager has <b>no authentication</b> by default — anyone reaching it can upload &amp; delete files.
      Enable the gate in <code>fileimagemanager/config/filemanager.php</code> before production. See the README.</div>
  </div>

<?php if (!$installed): ?>

  <div class="card">
    <div class="hd"><h2>Run setup first</h2></div>
    <div class="bd">
      <p class="muted">The bundle hasn't been assembled yet. Run the setup script in this folder to download TinyMCE,
        the language packs and the File &amp; Image Manager:</p>
      <ol class="setup-steps">
        <li><b>Windows (PowerShell):</b> &nbsp;<code class="k">.\setup.ps1</code></li>
        <li><b>Linux / macOS:</b> &nbsp;<code class="k">chmod +x setup.sh &amp;&amp; ./setup.sh</code></li>
        <li>Reload this page.</li>
      </ol>
    </div>
  </div>

<?php else: ?>

  <div class="grid">
    <!-- Live editor -->
    <section class="card">
      <div class="hd">
        <h2>Live editor</h2>
        <span class="tag">click the <b>browse</b> button in the toolbar to open the manager</span>
      </div>
      <div class="bd">
        <textarea id="editor" class="wysiwyg"><h2>Hello 👋</h2>
<p>This is <strong>TinyMCE 8</strong> with the custom <em>Lucide</em> icon pack and the modern
<a href="https://github.com/radekhulan/fileimagemanager">File &amp; Image Manager</a>.</p>
<p>Use the image, media or the dedicated <strong>file manager</strong> toolbar button to insert files.</p>
<ul><li>Drag &amp; drop uploads</li><li>Built-in image editor</li><li>Light &amp; dark themes</li></ul>
</textarea>
        <div class="editor-actions">
          <button class="btn primary" type="button" onclick="window.open('<?= e($managerUrl) ?>','_blank')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/></svg>
            Open manager (standalone)
          </button>
          <button class="btn" type="button" onclick="showHtml()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 16 4-4-4-4"/><path d="m6 8-4 4 4 4"/><path d="m14.5 4-5 16"/></svg>
            View HTML
          </button>
          <button class="btn" id="themeBtn" type="button" onclick="toggleDark()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
            <span id="themeLabel">Dark</span>
          </button>
        </div>
      </div>
    </section>

    <!-- Side panel -->
    <aside class="side">
      <section class="card">
        <div class="hd"><h2>Try a language</h2></div>
        <div class="bd">
          <div class="field">
            <label class="lbl" for="lang">Editor UI language</label>
            <select id="lang" onchange="setLang(this.value)">
              <?php foreach ($langs as $code => $name): ?>
                <option value="<?= e($code) ?>"<?= $code === '' ? ' selected' : '' ?>><?= e($name) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <p class="muted" style="margin:.4rem 0 0;font-size:12.5px"><?= count($langs) ?> language packs installed.</p>
        </div>
      </section>

      <section class="card">
        <div class="hd"><h2>What's inside</h2></div>
        <div class="bd">
          <ul class="facts">
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> TinyMCE 8 community (GPL)</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Custom Lucide icon pack + skin</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> File &amp; Image Manager (CC0)</li>
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Drag-drop upload + image editor</li>
          </ul>
        </div>
      </section>
    </aside>

    <!-- Quick use — full width under the editor -->
    <section class="card quickuse">
      <div class="hd"><h2>Quick use</h2><span class="tag">copy &amp; paste — drop it into your page</span></div>
      <div class="bd">
        <div class="snip-wrap">
          <button class="btn copy" type="button" onclick="copySnippet(this)">Copy</button>
          <pre class="snippet" id="snippet"><?= e($snippet) ?></pre>
        </div>
      </div>
    </section>
  </div>

  <dialog id="html">
    <div class="hd"><h3>Editor HTML</h3><button class="btn" style="margin-left:auto" onclick="document.getElementById('html').close()">Close</button></div>
    <pre id="htmlOut"></pre>
  </dialog>

<?php endif; ?>

  <!-- ───────────────────────── docs / info ───────────────────────── -->

  <div class="section" id="overview">
    <div class="shd"><h2>Overview</h2><span class="ln"></span></div>
    <p class="muted" style="max-width:780px;margin:0 0 16px">
      A ready-to-run bundle that glues together the GPL build of <b>TinyMCE 8</b>, a custom <b>Lucide</b> icon
      pack with a refined skin, and Radek Hulán's modern <b>File &amp; Image Manager</b>. One script downloads and
      wires everything together — no Composer, no npm build. Re-run it any time to update to the latest versions.
    </p>
    <div class="panelcard">
      <table class="kv">
        <tr><th>TinyMCE 8 <span class="badge gpl">GPL v2+</span></th><td>Community build + all 60 language packs, fetched from <code>download.tiny.cloud</code>.</td></tr>
        <tr><th>Custom Lucide icons</th><td>Outlined icon pack (registered as <code>lucide</code>) + <code>custom.css</code> skin overrides; the stock <code>default</code> pack is neutralised.</td></tr>
        <tr><th>File &amp; Image Manager <span class="badge cc0">CC0</span></th><td>Vue 3 + PHP 8 file browser with drag-drop upload &amp; image editor, from the prebuilt GitHub release.</td></tr>
        <tr><th>Glue</th><td>A demo page, an IIS <code>web.config</code> / Apache <code>.htaccess</code>, and the config patch that stores uploads in the repo's <code>media/</code> folder.</td></tr>
      </table>
    </div>
  </div>

  <div class="section" id="features">
    <div class="shd"><h2>Features</h2><span class="ln"></span></div>
    <div class="cols">
      <div class="feat"><h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15V6a2 2 0 0 0-2-2H9a2 2 0 0 0-2 2v9"/><rect width="18" height="6" x="3" y="15" rx="2"/></svg>Browse &amp; search</h3><p>Grid, list and column views, breadcrumbs, instant search, type filters and sorting.</p></div>
      <div class="feat"><h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5-5 5 5"/><path d="M12 5v14"/></svg>Upload &amp; manage</h3><p>Drag-drop or paste-from-URL uploads, copy / cut / paste, rename, duplicate, ZIP extract.</p></div>
      <div class="feat"><h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v8"/><path d="M8 12h8"/></svg>Image editor</h3><p>Crop, rotate, resize, filters, annotations and watermark — saved straight back to the server.</p></div>
      <div class="feat"><h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>Editor integration</h3><p>One-click toolbar button, native file picker for image / media / link dialogs, smart HTML insertion.</p></div>
      <div class="feat"><h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.9 4.9 1.4 1.4"/><path d="m17.7 17.7 1.4 1.4"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.3 17.7-1.4 1.4"/><path d="m19.1 4.9-1.4 1.4"/></svg>Light &amp; dark</h3><p>Auto-detects the system theme with a manual toggle; responsive down to mobile.</p></div>
      <div class="feat"><h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/></svg>Localised</h3><p>All TinyMCE language packs plus the manager's own translations; pick the UI language at init.</p></div>
    </div>
  </div>

  <div class="section" id="integration">
    <div class="shd"><h2>Use it in your app</h2><span class="ln"></span></div>
    <p class="muted" style="margin:0 0 14px">Copy the snippet from the <b>Quick use</b> panel above, then point a <code class="inl">&lt;textarea class="wysiwyg"&gt;</code> at it. The options that make this bundle special:</p>
    <div class="panelcard">
      <table class="kv">
        <tr><th><code>icons: 'lucide'</code></th><td>Loads the custom Lucide pack from <code>tinymce/icons/lucide/</code> and auto-injects <code>custom.css</code>.</td></tr>
        <tr><th><code>license_key: 'gpl'</code></th><td>Required to run the community build without a Tiny Cloud key.</td></tr>
        <tr><th><code>external_plugins.fileimagemanager</code></th><td>Path to <code>fileimagemanager/public/tinymce/plugin.js</code> — adds the toolbar button &amp; file picker.</td></tr>
        <tr><th><code>fileimagemanager_url</code></th><td>URL of <code>fileimagemanager/public/</code>; the dialog opens it in a same-origin iframe.</td></tr>
        <tr><th><code>fileimagemanager_title</code></th><td>Optional dialog title. Cross-domain mode via <code>fileimagemanager_crossdomain</code>.</td></tr>
      </table>
    </div>
  </div>

  <div class="section" id="ops">
    <div class="shd"><h2>Updating &amp; security</h2><span class="ln"></span></div>
    <div class="twocol">
      <div class="block">
        <h3>Updating</h3>
        <p>Re-run the setup script — it pulls the latest TinyMCE and File &amp; Image Manager and re-applies the customisations.</p>
        <ul>
          <li>Your uploads in <code class="inl">media/source</code> &amp; <code class="inl">media/thumbs</code> are kept.</li>
          <li>An edited <code class="inl">config/filemanager.php</code> is preserved.</li>
          <li>Pin versions with <code class="inl">-TinymceVersion</code> / <code class="inl">--fim-version</code>.</li>
        </ul>
        <pre>.\setup.ps1            # Windows
./setup.sh             # Linux / macOS</pre>
      </div>
      <div class="block warnbox">
        <h3>⚠ Security — read this</h3>
        <p>The manager ships <b>without authentication</b> so the demo runs instantly. Anyone who can reach it can upload, rename and <b>delete</b> files.</p>
        <ul>
          <li><b>Session gate:</b> uncomment the block at the top of <code class="inl">config/filemanager.php</code> and set <code class="inl">$_SESSION['ImageEditorAllowed']</code> after your login.</li>
          <li><b>Access keys:</b> set <code class="inl">use_access_keys =&gt; true</code> + a long random <code class="inl">access_keys</code>, open with <code class="inl">?akey=…</code>.</li>
          <li>Or keep it behind your admin auth / VPN / IP allow-list.</li>
        </ul>
      </div>
    </div>
  </div>

  <div class="section" id="licenses">
    <div class="shd"><h2>Licenses</h2><span class="ln"></span></div>
    <div class="twocol">
      <div class="block">
        <h3>TinyMCE <span class="badge gpl">GPL v2+</span></h3>
        <p>© Tiny Technologies, Inc. The community build is used under the GNU GPL v2 (or later) with <code class="inl">license_key: 'gpl'</code>. Commercial, non-GPL use requires a Tiny Cloud subscription.</p>
        <p style="margin:0"><a href="LICENSE-TINYMCE.txt">LICENSE-TINYMCE.txt</a> · <a href="https://github.com/tinymce/tinymce" target="_blank" rel="noopener">tinymce/tinymce ↗</a></p>
      </div>
      <div class="block">
        <h3>File &amp; Image Manager <span class="badge cc0">CC0 1.0</span></h3>
        <p>By <a href="https://mywebdesign.dev/" target="_blank" rel="noopener">Radek Hulán</a>. Released into the public domain (CC0 1.0) — use, modify and redistribute without restriction.</p>
        <p style="margin:0"><a href="LICENSE-FILEIMAGEMANAGER.txt">LICENSE-FILEIMAGEMANAGER.txt</a> · <a href="https://github.com/radekhulan/fileimagemanager" target="_blank" rel="noopener">radekhulan/fileimagemanager ↗</a></p>
      </div>
    </div>
  </div>

  <footer>
    Bundle &amp; custom icons <a href="LICENSE">CC0 1.0</a> ·
    TinyMCE <a href="LICENSE-TINYMCE.txt">GPL v2+</a> ·
    File &amp; Image Manager <a href="LICENSE-FILEIMAGEMANAGER.txt">CC0 1.0</a> ·
    icon shapes from <a href="https://lucide.dev/" target="_blank" rel="noopener">Lucide</a> (ISC)<?php if ($ver['updated']): ?> ·
    built <?= e((string) $ver['updated']) ?><?php endif; ?>
  </footer>
</div>

<?php if ($installed): ?>
<script src="<?= e($tinymceJs) ?>"></script>
<script>
  var MANAGER_URL = <?= json_encode($managerUrl) ?>;
  var editorLang = '';
  var editorDark = false;

  function initEditor() {
    tinymce.remove('#editor');
    var cfg = {
      selector: '#editor',
      license_key: 'gpl',
      skin: editorDark ? 'oxide-dark' : 'oxide',
      content_css: editorDark ? 'dark' : 'default',
      icons: 'lucide',
      branding: false,
      promotion: false,
      menubar: false,
      toolbar_mode: 'floating',
      autoresize_min_height: 360,
      fileimagemanager_url: MANAGER_URL,
      fileimagemanager_dragdrop: true,
      relative_urls: false,
      plugins: 'quickbars autoresize anchor advlist autolink table code link lists image media fileimagemanager fullscreen visualblocks searchreplace',
      toolbar1: 'blocks | bold italic underline strikethrough removeformat | bullist numlist | alignleft aligncenter alignright | link unlink | blockquote',
      toolbar2: 'undo redo | table hr | image media fileimagemanager | searchreplace visualblocks | fullscreen code'
    };
    if (editorLang) { cfg.language = editorLang; }
    tinymce.init(cfg);
  }

  function setLang(v) { editorLang = v; initEditor(); }

  function toggleDark() {
    editorDark = !editorDark;
    document.documentElement.classList.toggle('tox-dark', editorDark);
    var label = document.getElementById('themeLabel');
    if (label) { label.textContent = editorDark ? 'Light' : 'Dark'; }
    initEditor();
  }

  function showHtml() {
    var ed = tinymce.get('editor');
    if (!ed) return;
    document.getElementById('htmlOut').textContent = ed.getContent();
    document.getElementById('html').showModal();
  }

  function copySnippet(btn) {
    var txt = document.getElementById('snippet').textContent;
    navigator.clipboard.writeText(txt).then(function () {
      var old = btn.textContent; btn.textContent = 'Copied ✓';
      setTimeout(function () { btn.textContent = old; }, 1400);
    });
  }

  initEditor();
</script>
<?php endif; ?>
</body>
</html>
