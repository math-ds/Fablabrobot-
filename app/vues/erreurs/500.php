<?php
$pageErrorCode = 500;
$pageErrorTitle = (string)($errorTitle ?? 'Erreur serveur');
$pageErrorMessage = (string)($errorMessage ?? 'Une erreur interne est survenue.');
$pageErrorAccent = '#ff8b6b';
require __DIR__ . '/_template.php';