<?php
$baseUrl = $GLOBALS['baseUrl'] ?? '/Fablabrobot/public/';
$publicFlashMessage = $_SESSION['message'] ?? null;
$publicFlashType = $_SESSION['message_type'] ?? 'info';
if ($publicFlashMessage !== null) {
    unset($_SESSION['message'], $_SESSION['message_type']);
}

$publicDefaultScripts = isset($publicDefaultScripts) && is_array($publicDefaultScripts)
    ? $publicDefaultScripts
    : [
        'js/ajax-helper.js',
        'js/favoris.js',
        'js/recherche-helper.js',
        'js/toast-notification.js',
        'js/public-notification.js',
        'js/image-fallback.js',
        'js/header-navigation.js',
        'js/modal-accessibility.js',
        'js/public-flash.js',
    ];

$publicScripts = isset($publicScripts) && is_array($publicScripts) ? $publicScripts : [];
$normalizedScripts = [];
foreach (array_merge($publicDefaultScripts, $publicScripts) as $scriptPath) {
    $path = trim((string)$scriptPath);
    if ($path === '') {
        continue;
    }

    if (!preg_match('#^(https?:)?//#', $path) && !str_starts_with($path, 'js/')) {
        $path = 'js/' . ltrim($path, '/');
    }

    if (!in_array($path, $normalizedScripts, true)) {
        $normalizedScripts[] = $path;
    }
}

function public_script_version(string $scriptPath): string
{
    if (preg_match('#^(https?:)?//#', $scriptPath)) {
        return '';
    }

    $relativePath = ltrim($scriptPath, '/');
    if (!str_starts_with($relativePath, 'js/')) {
        $relativePath = 'js/' . $relativePath;
    }

    $absolutePath = dirname(__DIR__, 3) . '/public/' . $relativePath;
    if (!is_file($absolutePath)) {
        return '';
    }

    $mtime = @filemtime($absolutePath);
    if ($mtime === false) {
        return '';
    }

    return '?v=' . (string)$mtime;
}
?>
<?php require __DIR__ . '/footer.php'; ?>

<?php if ($publicFlashMessage !== null): ?>
  <div
    id="publicFlashData"
    hidden
    data-flash-type="<?= htmlspecialchars((string)$publicFlashType, ENT_QUOTES, 'UTF-8') ?>"
    data-flash-message="<?= htmlspecialchars((string)$publicFlashMessage, ENT_QUOTES, 'UTF-8') ?>"></div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php foreach ($normalizedScripts as $scriptPath): ?>
  <?php
  $scriptSrc = preg_match('#^(https?:)?//#', $scriptPath)
      ? $scriptPath
      : $baseUrl . $scriptPath;
  ?>
  <script src="<?= htmlspecialchars($scriptSrc . public_script_version($scriptPath), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php endforeach; ?>
</body>
</html>
