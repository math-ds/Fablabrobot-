<?php

require_once __DIR__ . '/../helpers/CsrfHelper.php';

function generateCsrfToken(): string
{
    return CsrfHelper::obtenirJeton();
}

function verifyCsrfToken($token): bool
{
    return CsrfHelper::validerJeton((string)$token);
}
