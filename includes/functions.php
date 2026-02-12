<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 17:08:00
 */

function calcularIdade($dataNascimento) {
    $nascimento = new DateTime($dataNascimento);
    $hoje = new DateTime();
    return $hoje->diff($nascimento)->y;
}

function formatarData($data) {
    return date('d/m/Y', strtotime($data));
}

function uploadFoto($arquivo, $subpasta = '') {
    if (!isset($arquivo) || $arquivo['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extensao, $extensoesPermitidas)) {
        return null;
    }
    
    $pastaBase = __DIR__ . '/../assets/uploads/';
    if ($subpasta) {
        $pastaBase .= $subpasta . '/';
    }
    
    if (!is_dir($pastaBase)) {
        mkdir($pastaBase, 0755, true);
    }
    
    $nomeArquivo = uniqid() . '.' . $extensao;
    $caminhoCompleto = $pastaBase . $nomeArquivo;
    
    if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
        return ($subpasta ? $subpasta . '/' : '') . $nomeArquivo;
    }
    
    return null;
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function setFlashMessage($mensagem, $tipo = 'success') {
    $_SESSION['flash_tipo'] = $tipo;
    $_SESSION['flash_mensagem'] = $mensagem;
}

function getFlashMessage() {
    if (isset($_SESSION['flash_mensagem'])) {
        $tipo = $_SESSION['flash_tipo'];
        $mensagem = $_SESSION['flash_mensagem'];
        unset($_SESSION['flash_tipo'], $_SESSION['flash_mensagem']);
        return ['tipo' => $tipo, 'mensagem' => $mensagem];
    }
    return null;
}
