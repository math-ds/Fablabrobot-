<?php

class AdminDashboardControleur {
    public function index() {
        if (!isset($_SESSION['utilisateur_role']) || strtolower($_SESSION['utilisateur_role']) !== 'admin') {
            header('Location: ?page=login');
            exit();
        }

        require __DIR__ . '/../vues/admin/dashboard-admin.php';
    }
}
