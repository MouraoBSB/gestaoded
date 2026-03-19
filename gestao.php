<?php
/**
 * Autor: Thiago Mourao
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 17:08:00
 *
 * Entrada da area restrita - redireciona para o dashboard do usuario
 */

require_once __DIR__ . '/includes/auth.php';
requireLogin();

$tipo = getUserType();

switch ($tipo) {
    case 'gestor':
        header('Location: /gestor/dashboard.php');
        break;
    case 'diretor':
        header('Location: /diretor/dashboard.php');
        break;
    case 'instrutor':
        header('Location: /instrutor/dashboard.php');
        break;
    default:
        logout();
}
exit;
