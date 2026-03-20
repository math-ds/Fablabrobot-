<?php



if (!class_exists('Pagination')) {
    require_once __DIR__ . '/../../helpers/Pagination.php';
}

if (!isset($pagination) || !($pagination instanceof Pagination)) {
    return;
}
?>
<nav class="pagination-nav" aria-label="Navigation par pages">
  <?= $pagination->rendrePagination() ?>
  <p class="pagination-info" aria-live="polite">
    <?php if ($pagination->totalPages() > 1): ?>
      Page <?= $pagination->pageCourante() ?> sur <?= $pagination->totalPages() ?> —
    <?php endif; ?>
    <span class="pagination-total"><?= $pagination->total() ?> résultat<?= $pagination->total() > 1 ? 's' : '' ?></span>
  </p>
</nav>
