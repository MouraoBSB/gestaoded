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
    if (isset($_SESSION['usuario_id'])) {
        return true;
    }
    
    if (isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $pdo = getConnection();

            // Garantir que coluna remember_token existe
            try {
                $pdo->query("SELECT remember_token FROM usuarios LIMIT 0");
            } catch (Exception $e) {
                $pdo->exec("ALTER TABLE usuarios ADD COLUMN remember_token VARCHAR(255) DEFAULT NULL");
            }

            $stmt = $pdo->prepare("SELECT id, nome, tipo FROM usuarios WHERE id = ? AND remember_token = ? AND ativo = 1");
            $stmt->execute([$_COOKIE['remember_user'], $_COOKIE['remember_token']]);
            $usuario = $stmt->fetch();

            if ($usuario) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_tipo'] = $usuario['tipo'];
                return true;
            }
        } catch (Exception $e) {
            // Falha silenciosa - limpar cookies inválidos
        }
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('remember_user', '', time() - 3600, '/');
    }
    
    return false;
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
        header('Location: /gestao.php');
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

function login($email, $senha, $lembrar = false) {
    require_once __DIR__ . '/../config/database.php';
    $pdo = getConnection();
    
    $stmt = $pdo->prepare("SELECT id, nome, senha, tipo, ativo FROM usuarios WHERE email = ? AND ativo = 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();
    
    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_tipo'] = $usuario['tipo'];
        $_SESSION['senha_padrao'] = ($senha === 'cema2026');
        
        if ($lembrar) {
            $token = bin2hex(random_bytes(32));
            $expiracao = time() + (30 * 24 * 60 * 60);
            
            setcookie('remember_token', $token, $expiracao, '/', '', false, true);
            setcookie('remember_user', $usuario['id'], $expiracao, '/', '', false, true);
            
            $stmt = $pdo->prepare("UPDATE usuarios SET remember_token = ? WHERE id = ?");
            $stmt->execute([$token, $usuario['id']]);
        }
        
        return true;
    }
    return false;
}

function logout() {
    if (isset($_COOKIE['remember_token']) && isset($_COOKIE['remember_user'])) {
        require_once __DIR__ . '/../config/database.php';
        $pdo = getConnection();
        
        $stmt = $pdo->prepare("UPDATE usuarios SET remember_token = NULL WHERE id = ?");
        $stmt->execute([$_COOKIE['remember_user']]);
        
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('remember_user', '', time() - 3600, '/');
    }
    
    session_destroy();
    header('Location: /login.php');
    exit;
}
