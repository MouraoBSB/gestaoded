<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 17:30:00
 */

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Teste de Conexão</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='bg-gray-100 min-h-screen flex items-center justify-center p-4'>
    <div class='bg-white rounded-lg shadow-2xl w-full max-w-2xl p-8'>";

echo "<h1 class='text-3xl font-bold text-gray-800 mb-6'>Teste de Conexão</h1>";

require_once __DIR__ . '/config/database.php';

echo "<div class='space-y-4'>";

echo "<div class='bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded'>
        <p class='font-bold'>Configurações do Banco de Dados:</p>
        <p class='text-sm'>Host: " . DB_HOST . "</p>
        <p class='text-sm'>Banco: " . DB_NAME . "</p>
        <p class='text-sm'>Usuário: " . DB_USER . "</p>
      </div>";

try {
    $pdo = getConnection();
    
    echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded'>
            <p class='font-bold'>✓ Conexão estabelecida com sucesso!</p>
            <p class='text-sm'>O sistema está pronto para ser usado.</p>
          </div>";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tabelas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tabelas) > 0) {
        echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded'>
                <p class='font-bold'>✓ Tabelas encontradas no banco:</p>
                <ul class='list-disc list-inside text-sm mt-2'>";
        foreach ($tabelas as $tabela) {
            echo "<li>$tabela</li>";
        }
        echo "</ul>
              </div>";
        
        echo "<div class='bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded'>
                <p class='font-bold'>Sistema pronto para uso!</p>
                <p class='text-sm mt-2'>
                    <a href='/login.php' class='underline font-semibold'>Clique aqui para fazer login</a>
                </p>
              </div>";
    } else {
        echo "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded'>
                <p class='font-bold'>⚠ Nenhuma tabela encontrada</p>
                <p class='text-sm mt-2'>
                    <a href='/instalar.php' class='underline font-semibold'>Clique aqui para instalar o banco de dados</a>
                </p>
              </div>";
    }
    
} catch (Exception $e) {
    echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded'>
            <p class='font-bold'>✗ Erro ao conectar ao banco de dados</p>
            <p class='text-sm'>" . htmlspecialchars($e->getMessage()) . "</p>
          </div>";
    
    echo "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mt-4'>
            <p class='font-bold'>Possíveis soluções:</p>
            <ul class='list-disc list-inside text-sm mt-2'>
                <li>Verifique se as credenciais em config/database.php estão corretas</li>
                <li>Verifique se o servidor de banco de dados está acessível</li>
                <li>Verifique sua conexão com a internet</li>
                <li>Verifique se o firewall não está bloqueando a conexão</li>
            </ul>
          </div>";
}

echo "</div>";

echo "<div class='mt-6 flex gap-3'>
        <a href='/instalar.php' class='flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-3 rounded-lg transition font-semibold'>
            Instalar Banco
        </a>
        <a href='/login.php' class='flex-1 bg-green-600 hover:bg-green-700 text-white text-center py-3 rounded-lg transition font-semibold'>
            Fazer Login
        </a>
      </div>";

echo "</div></body></html>";
?>
