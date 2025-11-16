<?php
$dir = __DIR__;

// Alle TXT & ZIP-Dateien alphabetisch einsammeln
$files = glob($dir . "/art/*.{txt,zip}", GLOB_BRACE);
sort($files, SORT_NATURAL | SORT_FLAG_CASE);

// Wenn keine Dateien existieren
if (!$files) {
    die("Keine TXT oder ZIP Dateien gefunden.");
}

// aktuelle Datei bestimmen
$current = isset($_GET['art']) ? intval($_GET['art']) : 0;
$current = max(0, min($current, count($files) - 1));

$path = $files[$current];
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

if ($ext === "zip") {
    $zip = new ZipArchive;
    if ($zip->open($path) === TRUE) {

        $content = null;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);

            if (strtolower(pathinfo($stat['name'], PATHINFO_EXTENSION)) === "txt") {
                $content = $zip->getFromIndex($i);
                break;
            }
        }

        $zip->close();

        if ($content === null) {
            $content = "[ZIP enthält keine lesbare TXT]";
        }

    } else {
        $content = "[ZIP konnte nicht geöffnet werden]";
    }

} else {
    // normale txt
    $content = file_get_contents($path);
}

$self = htmlspecialchars($_SERVER['SCRIPT_NAME'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ASCII Viewer</title>

<style>
  html, body {
    margin: 0;
    height: 100%;
    background-color: #666666;
    color: #fff;
    font-family: monospace;
    overflow: hidden;
  }

  /* Vollflächiger Wrapper, zentriert */
  .wrapper {
    position: fixed;
    inset: 0;              /* top:0; right:0; bottom:0; left:0; */
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
  }

  /* Inner relativ, damit pre absolut mittig positioniert werden kann */
  .inner {
    position: relative;
    width: 100%;
    height: 100%;
    overflow: hidden;
  }

  pre {
    margin: 0;
    padding: 0;
    white-space: pre;
    line-height: 1;
    font-family: monospace;
    color: #fff;

    position: absolute;
    left: 50%;
    top: 50%;
    transform-origin: center center;
    will-change: transform;
  }

  /* Pfeile */
  .arrow {
    position: fixed;
    top: 50%;
    transform: translateY(-50%);
    font-size: 48px;
    color: #fff;
    text-decoration: none;
    padding: 8px 12px;
    user-select: none;
    cursor: pointer;
    z-index: 50;
  }
  .arrow-left { left: 8px; }
  .arrow-right { right: 8px; }

  .disabled {
    visibility: hidden;
    pointer-events: none;
  }

  @media (max-width: 480px) {
    .arrow { font-size: 36px; padding: 6px; }
  }
</style>
</head>
<body>

<!-- Zurück -->
<a class="arrow arrow-left <?= ($current == 0) ? 'disabled' : '' ?>"
   href="<?= $self ?>?art=<?= max(0, $current - 1) ?>" aria-label="Zurück">◀</a>

<div class="wrapper">
  <div class="inner">
    <pre id="ascii"><?= htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></pre>
  </div>
</div>

<!-- Vor -->
<a class="arrow arrow-right <?= ($current >= count($files)-1) ? 'disabled' : '' ?>"
   href="<?= $self ?>?art=<?= min(count($files) - 1, $current + 1) ?>" aria-label="Vor">▶</a>

<script>
(function() {
  const pre = document.getElementById('ascii');
  const inner = document.querySelector('.inner');

  function autoScale() {
    // setze Transform zurück auf neutralen Zustand (translate + scale 1),
    // damit Messungen die "natürliche" Größe widerspiegeln
    pre.style.transform = 'translate(-50%,-50%) scale(1)';

    // Inhaltliche Größe (natürliche Breite/Höhe)
    const contentWidth = pre.scrollWidth;
    const contentHeight = pre.scrollHeight;

    // Verfügbare Größe (Container)
    const availW = inner.clientWidth;
    const availH = inner.clientHeight;

    if (!contentWidth || !contentHeight) return;

    // Sicherheits-Padding: etwas Abstand zum Rand (in px)
    const pad = 8; // kannst du anpassen; sorgt für kleine Lücke zum Rand
    const scaleX = (availW - pad*2) / contentWidth;
    const scaleY = (availH - pad*2) / contentHeight;

    // wir wählen die kleinere Skalierung, damit nichts abgeschnitten wird
    let scale = Math.min(scaleX, scaleY);

    // kleine Sicherheitsmarge wegen Rundungsfehlern
    const epsilon = 0.995;
    scale = scale * epsilon;

    // optional: wenn du nicht möchtest, dass der Text vergrößert wird über 100%, entferne die nächste Zeile
    // scale = Math.min(scale, 1);

    // wende translate + scale an (translate sorgt für perfekte Zentrierung)
    pre.style.transform = 'translate(-50%,-50%) scale(' + scale + ')';
  }

  window.addEventListener('load', autoScale);
  window.addEventListener('resize', autoScale);

  // Keyboard navigation (optional)
  window.addEventListener('keydown', function(e) {
    if (e.key === 'ArrowLeft') {
      const left = document.querySelector('.arrow-left:not(.disabled)');
      if (left) window.location.href = left.href;
    } else if (e.key === 'ArrowRight') {
      const right = document.querySelector('.arrow-right:not(.disabled)');
      if (right) window.location.href = right.href;
    }
  });
})();
</script>

</body>
</html>