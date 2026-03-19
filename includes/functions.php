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

function getSeoConfigs() {
    static $cache = null;
    if ($cache !== null) return $cache;

    try {
        $pdo = getConnection();
        $stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'seo_%'");
        $configs = [];
        foreach ($stmt->fetchAll() as $row) {
            $configs[$row['chave']] = $row['valor'];
        }
        $cache = $configs;
        return $configs;
    } catch (Exception $e) {
        return [];
    }
}

function renderSeoMeta($pageTitle = null, $pageDescription = null, $pageImage = null) {
    $seo = getSeoConfigs();

    $siteTitle = $seo['seo_titulo_site'] ?? 'Gestão de Cursos - CEMA';
    $title = $pageTitle ? $pageTitle . ' - ' . $siteTitle : $siteTitle;
    $description = $pageDescription ?: ($seo['seo_descricao'] ?? '');
    $keywords = $seo['seo_palavras_chave'] ?? '';
    $favicon = $seo['seo_favicon'] ?? '';
    $ogImage = $pageImage ?: ($seo['seo_og_imagem'] ?? '');
    $ogTitle = $pageTitle ?: ($seo['seo_og_titulo'] ?? $siteTitle);
    $ogDescription = $pageDescription ?: ($seo['seo_og_descricao'] ?? $description);
    $siteUrl = $seo['seo_url_site'] ?? '';
    $themeColor = $seo['seo_cor_tema'] ?? '#4e4483';

    $html = '';

    if ($description) {
        $html .= '    <meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";
    }
    if ($keywords) {
        $html .= '    <meta name="keywords" content="' . htmlspecialchars($keywords) . '">' . "\n";
    }
    $html .= '    <meta name="theme-color" content="' . htmlspecialchars($themeColor) . '">' . "\n";

    // Open Graph
    $html .= '    <meta property="og:type" content="website">' . "\n";
    $html .= '    <meta property="og:title" content="' . htmlspecialchars($ogTitle) . '">' . "\n";
    if ($ogDescription) {
        $html .= '    <meta property="og:description" content="' . htmlspecialchars($ogDescription) . '">' . "\n";
    }
    if ($ogImage) {
        $imgUrl = $siteUrl ? rtrim($siteUrl, '/') . '/assets/uploads/seo/' . $ogImage : '/assets/uploads/seo/' . $ogImage;
        $html .= '    <meta property="og:image" content="' . htmlspecialchars($imgUrl) . '">' . "\n";
    }
    if ($siteUrl) {
        $html .= '    <meta property="og:url" content="' . htmlspecialchars($siteUrl) . '">' . "\n";
    }

    // Twitter Card
    $html .= '    <meta name="twitter:card" content="summary_large_image">' . "\n";
    $html .= '    <meta name="twitter:title" content="' . htmlspecialchars($ogTitle) . '">' . "\n";
    if ($ogDescription) {
        $html .= '    <meta name="twitter:description" content="' . htmlspecialchars($ogDescription) . '">' . "\n";
    }
    if ($ogImage) {
        $imgUrl = $siteUrl ? rtrim($siteUrl, '/') . '/assets/uploads/seo/' . $ogImage : '/assets/uploads/seo/' . $ogImage;
        $html .= '    <meta name="twitter:image" content="' . htmlspecialchars($imgUrl) . '">' . "\n";
    }

    // Favicon
    if ($favicon) {
        $ext = strtolower(pathinfo($favicon, PATHINFO_EXTENSION));
        $mimeTypes = ['ico' => 'image/x-icon', 'png' => 'image/png', 'svg' => 'image/svg+xml', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'];
        $mime = $mimeTypes[$ext] ?? 'image/x-icon';
        $html .= '    <link rel="icon" type="' . $mime . '" href="/assets/uploads/seo/' . htmlspecialchars($favicon) . '">' . "\n";
        $html .= '    <link rel="shortcut icon" type="' . $mime . '" href="/assets/uploads/seo/' . htmlspecialchars($favicon) . '">' . "\n";
        if ($ext === 'png') {
            $html .= '    <link rel="apple-touch-icon" href="/assets/uploads/seo/' . htmlspecialchars($favicon) . '">' . "\n";
        }
    }

    return $html;
}
