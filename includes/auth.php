<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 17:08:00
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['usuario_tipo'], (array)$roles)) {
        header('Location: /index.php');
        exit;
    }
}

function getUserId() {
    return $_SESSION['usuario_id'] ?? null;
}

function getUserName() {
    return $_SESSION['usuario_nome'] ?? '';
}

function getUserType() {
    return $_SESSION['usuario_tipo'] ?? '';
}

function login($email, $senha) {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("SELECT id, nome, senha, tipo, ativo FROM usuarios WHERE email = ? AND ativo = 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_tipo'] = $usuario['tipo'];
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: /login.php');
    exit;
}
