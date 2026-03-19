<?php
require_once __DIR__ . '/config/database.php';

$pdo = getConnection();

echo "<h1>Diagnóstico da Tabela Conclusoes</h1>";

// Verificar se a tabela existe
$stmt = $pdo->query("SHOW TABLES LIKE 'conclusoes'");
$existe = $stmt->fetch();

if ($existe) {
    echo "<p style='color: green;'>✓ Tabela conclusoes existe</p>";
    
    // Mostrar estrutura
    echo "<h2>Estrutura da Tabela:</h2>";
    $stmt = $pdo->query("DESCRIBE conclusoes");
    $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($colunas as $col) {
        echo "<tr>";
        echo "<td><strong>" . $col['Field'] . "</strong></td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . ($col['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar campos específicos
    echo "<h2>Verificação de Campos:</h2>";
    $temAprovado = false;
    $temStatus = false;
    $temAnoConlusao = false;
    
    foreach ($colunas as $col) {
        if ($col['Field'] === 'aprovado') $temAprovado = true;
        if ($col['Field'] === 'status') $temStatus = true;
        if ($col['Field'] === 'ano_conclusao') $temAnoConlusao = true;
    }
    
    echo "<p>Campo 'aprovado': " . ($temAprovado ? "<span style='color:green'>✓ EXISTE</span>" : "<span style='color:red'>✗ NÃO EXISTE</span>") . "</p>";
    echo "<p>Campo 'status': " . ($temStatus ? "<span style='color:green'>✓ EXISTE</span>" : "<span style='color:red'>✗ NÃO EXISTE</span>") . "</p>";
    echo "<p>Campo 'ano_conclusao': " . ($temAnoConlusao ? "<span style='color:green'>✓ EXISTE</span>" : "<span style='color:red'>✗ NÃO EXISTE</span>") . "</p>";
    
    // Contar registros
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM conclusoes");
    $total = $stmt->fetch();
    echo "<p>Total de registros: <strong>" . $total['total'] . "</strong></p>";
    
} else {
    echo "<p style='color: red;'>✗ Tabela conclusoes NÃO existe!</p>";
    echo "<p>Execute: <a href='/instalar.php'>instalar.php</a></p>";
}
