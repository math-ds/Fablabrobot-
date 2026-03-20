<?php
$pageErrorCode = 403;
$pageErrorTitle = (string)($errorTitle ?? 'Accès refusé');
$pageErrorMessage = (string)($errorMessage ?? 'Vous ne pouvez pas accéder à cette page.');
$pageErrorAccent = '#ff6b6b';
require __DIR__ . '/_template.php';
