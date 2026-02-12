<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 18:02:00
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

$pageTitle = 'Atualizar Sistema';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-2xl w-full max-w-4xl p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Atualizar Sistema de Chamada</h1>
        
        <?php
        try {
            $pdo = getConnection();
            echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4'>
                    <p class='font-bold'>✓ Conexão com banco de dados estabelecida</p>
                  </div>";
            
            $sqlFile = __DIR__ . '/atualizar_banco.sql';
            if (!file_exists($sqlFile)) {
                throw new Exception("Arquivo atualizar_banco.sql não encontrado!");
            }
            
            $sql = file_get_contents($sqlFile);
            $comandos = array_filter(array_map('trim', explode(';', $sql)));
            
            echo "<div class='bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4'>
                    <p class='font-bold'>Executando atualizações...</p>
                  </div>";
            
            echo "<div class='space-y-2 mb-4'>";
            
            $sucesso = 0;
            $erros = 0;
            
            foreach ($comandos as $comando) {
                $comando = trim($comando);
                if (empty($comando) || strpos($comando, '--') === 0) {
                    continue;
                }
                
                try {
                    $pdo->exec($comando);
                    
                    if (stripos($comando, 'CREATE TABLE') !== false) {
                        preg_match('/CREATE TABLE.*?(\w+)/i', $comando, $matches);
                        $tabela = $matches[1] ?? 'desconhecida';
                        echo "<div class='text-green-700'>✓ Tabela '$tabela' criada/verificada</div>";
                        $sucesso++;
                    } elseif (stripos($comando, 'ALTER TABLE') !== false) {
                        preg_match('/ALTER TABLE\s+(\w+)/i', $comando, $matches);
                        $tabela = $matches[1] ?? 'desconhecida';
                        echo "<div class='text-green-700'>✓ Tabela '$tabela' alterada</div>";
                        $sucesso++;
                    } elseif (stripos($comando, 'INSERT INTO') !== false) {
                        echo "<div class='text-green-700'>✓ Dados inseridos</div>";
                        $sucesso++;
                    }
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                        echo "<div class='text-yellow-600'>⚠ Coluna já existe (ignorado)</div>";
                    } elseif (strpos($e->getMessage(), 'already exists') !== false) {
                        echo "<div class='text-yellow-600'>⚠ Já existe (ignorado)</div>";
                    } else {
                        echo "<div class='text-red-700'>✗ Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
                        $erros++;
                    }
                }
            }
            
            echo "</div>";
            
            if ($erros === 0) {
                echo "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4'>
                        <p class='font-bold'>✓ ATUALIZAÇÃO CONCLUÍDA COM SUCESSO!</p>
                        <p class='text-sm mt-2'>$sucesso operações executadas</p>
                      </div>";
                
                echo "<div class='bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4'>
                        <p class='font-bold'>Novas funcionalidades disponíveis:</p>
                        <ul class='list-disc list-inside text-sm mt-2'>
                            <li>Gerenciamento de Instrutores com foto</li>
                            <li>Configurações SMTP para reset de senha</li>
                            <li>Suporte para múltiplos instrutores por curso</li>
                            <li>Upload de capa para cursos</li>
                            <li>Sistema de crop de imagens</li>
                        </ul>
                      </div>";
            } else {
                echo "<div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4'>
                        <p class='font-bold'>⚠ Atualização concluída com avisos</p>
                        <p class='text-sm mt-2'>$sucesso operações bem-sucedidas, $erros erros encontrados</p>
                      </div>";
            }
            
            echo "<div class='flex gap-3'>
                    <a href='/login.php' class='flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-3 rounded-lg transition font-semibold'>
                        Acessar Sistema
                    </a>
                    <a href='/gestor/dashboard.php' class='flex-1 bg-green-600 hover:bg-green-700 text-white text-center py-3 rounded-lg transition font-semibold'>
                        Dashboard
                    </a>
                  </div>";
            
            echo "<div class='mt-4 text-center text-sm text-gray-600'>
                    <p>Por segurança, delete este arquivo (atualizar.php) após a atualização.</p>
                  </div>";
            
        } catch (Exception $e) {
            echo "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded'>
                    <p class='font-bold'>✗ ERRO:</p>
                    <p class='text-sm'>" . htmlspecialchars($e->getMessage()) . "</p>
                  </div>";
        }
        ?>
    </div>
</body>
</html>
