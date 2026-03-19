<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-03-03 00:05:00
 */

require_once __DIR__ . '/config/database.php';

$pdo = getConnection();

echo "<h2>Configurações SMTP no Banco de Dados:</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Chave</th><th>Valor</th><th>Tamanho</th></tr>";

$stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'smtp_%' ORDER BY chave");
$configs = $stmt->fetchAll();

if (empty($configs)) {
    echo "<tr><td colspan='3'>Nenhuma configuração SMTP encontrada!</td></tr>";
} else {
    foreach ($configs as $config) {
        $valor = $config['valor'];
        $tamanho = strlen($valor);
        $valorExibido = $config['chave'] === 'smtp_senha' ? str_repeat('*', $tamanho) . " (senha oculta)" : htmlspecialchars($valor);
        
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($config['chave']) . "</strong></td>";
        echo "<td>" . $valorExibido . "</td>";
        echo "<td>" . $tamanho . " caracteres</td>";
        echo "</tr>";
    }
}

echo "</table>";

echo "<br><h3>Teste de Leitura das Configurações:</h3>";
echo "<pre>";
$stmt = $pdo->query("SELECT chave, valor FROM configuracoes WHERE chave LIKE 'smtp_%'");
$configsArray = [];
foreach ($stmt->fetchAll() as $row) {
    $configsArray[$row['chave']] = $row['valor'];
}

echo "Host: " . ($configsArray['smtp_host'] ?? 'NÃO DEFINIDO') . "\n";
echo "Porta: " . ($configsArray['smtp_port'] ?? 'NÃO DEFINIDO') . "\n";
echo "Usuário: " . ($configsArray['smtp_usuario'] ?? 'NÃO DEFINIDO') . "\n";
echo "Senha: " . (isset($configsArray['smtp_senha']) ? str_repeat('*', strlen($configsArray['smtp_senha'])) . ' (' . strlen($configsArray['smtp_senha']) . ' chars)' : 'NÃO DEFINIDO') . "\n";
echo "Email Remetente: " . ($configsArray['smtp_de_email'] ?? 'NÃO DEFINIDO') . "\n";
echo "Nome Remetente: " . ($configsArray['smtp_de_nome'] ?? 'NÃO DEFINIDO') . "\n";
echo "Segurança: " . ($configsArray['smtp_seguranca'] ?? 'NÃO DEFINIDO') . "\n";
echo "</pre>";

echo "<br><a href='/gestor/configuracoes.php'>← Voltar para Configurações</a>";
?>
