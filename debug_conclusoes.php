<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 19:45:00
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Tabela Conclusões</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-2xl font-bold mb-6">Debug - Estrutura da Tabela conclusoes</h1>
        
        <?php
        try {
            $pdo = getConnection();
            
            echo "<div class='mb-6'>";
            echo "<h2 class='text-xl font-bold mb-3'>Verificando se a tabela existe...</h2>";
            
            $stmt = $pdo->query("SHOW TABLES LIKE 'conclusoes'");
            $existe = $stmt->fetch();
            
            if ($existe) {
                echo "<p class='text-green-600 font-bold'>✓ Tabela 'conclusoes' existe!</p>";
                
                echo "<h2 class='text-xl font-bold mt-6 mb-3'>Estrutura da tabela:</h2>";
                echo "<div class='bg-gray-50 p-4 rounded-lg'>";
                
                $stmt = $pdo->query("DESCRIBE conclusoes");
                $colunas = $stmt->fetchAll();
                
                echo "<table class='w-full'>";
                echo "<thead><tr class='border-b-2'>";
                echo "<th class='text-left p-2'>Campo</th>";
                echo "<th class='text-left p-2'>Tipo</th>";
                echo "<th class='text-left p-2'>Null</th>";
                echo "<th class='text-left p-2'>Key</th>";
                echo "<th class='text-left p-2'>Default</th>";
                echo "</tr></thead>";
                echo "<tbody>";
                
                foreach ($colunas as $col) {
                    echo "<tr class='border-b'>";
                    echo "<td class='p-2 font-mono'>{$col['Field']}</td>";
                    echo "<td class='p-2 font-mono'>{$col['Type']}</td>";
                    echo "<td class='p-2'>{$col['Null']}</td>";
                    echo "<td class='p-2'>{$col['Key']}</td>";
                    echo "<td class='p-2'>{$col['Default']}</td>";
                    echo "</tr>";
                }
                
                echo "</tbody></table>";
                echo "</div>";
                
                $temStatus = false;
                foreach ($colunas as $col) {
                    if ($col['Field'] === 'status') {
                        $temStatus = true;
                        break;
                    }
                }
                
                if ($temStatus) {
                    echo "<p class='text-green-600 font-bold mt-4'>✓ Coluna 'status' encontrada!</p>";
                } else {
                    echo "<p class='text-red-600 font-bold mt-4'>✗ Coluna 'status' NÃO encontrada!</p>";
                    echo "<p class='mt-2'>A tabela existe mas está com estrutura incorreta.</p>";
                }
                
            } else {
                echo "<p class='text-red-600 font-bold'>✗ Tabela 'conclusoes' NÃO existe!</p>";
                echo "<p class='mt-2'>Você precisa executar a atualização do banco de dados.</p>";
            }
            
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='bg-red-100 border-2 border-red-500 p-4 rounded-lg'>";
            echo "<p class='text-red-800 font-bold'>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "</div>";
        }
        ?>
        
        <div class="mt-6 flex gap-3">
            <a href="/atualizar_banco.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold">
                Ir para Atualização
            </a>
            <a href="/gestao.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold">
                Voltar ao Sistema
            </a>
        </div>
    </div>
</body>
</html>
