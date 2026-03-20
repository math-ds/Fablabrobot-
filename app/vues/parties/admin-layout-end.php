<?php
$adminScripts = isset($adminScripts) && is_array($adminScripts) ? $adminScripts : [];
$adminAssetBase = $adminAssetBase ?? '';
$defaultScripts = ['js/admin-page-init.js', 'js/admin-mobile-menu.js', 'js/modal-accessibility.js'];

$normalizedScripts = [];
foreach (array_merge($adminScripts, $defaultScripts) as $scriptPath) {
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

function admin_script_version(string $scriptPath): string
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
    <?php foreach ($normalizedScripts as $scriptPath): ?>
        <script src="<?= htmlspecialchars($adminAssetBase . $scriptPath . admin_script_version($scriptPath), ENT_QUOTES, 'UTF-8') ?>"></script>
    <?php endforeach; ?>
    </main>
</div>
</body>
</html>
