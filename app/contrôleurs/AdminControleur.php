<?php

require_once __DIR__ . '/AdminDashboardControleur.php';

class AdminControleur
{
    public function index(): void
    {
        (new AdminDashboardControleur())->index();
    }
}
