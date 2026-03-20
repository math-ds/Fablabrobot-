<?php
$pageErrorCode = 404;
$pageErrorTitle = (string)($errorTitle ?? 'Page introuvable');
$pageErrorMessage = (string)($errorMessage ?? 'La page demandée est introuvable ou a été déplacée.');
$pageErrorAccent = '#f4b400';
require __DIR__ . '/_template.php';

