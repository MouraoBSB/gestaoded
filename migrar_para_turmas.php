<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-14 14:56:00
 * 
 * Script de migração para sistema de turmas
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$executado = false;
$mensagens = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executar'])) {
    try {
        $pdo = getConnection();
        $pdo->beginTransaction();
        $executado = true;
        
        // 1. Adicionar campos necessários na tabela cursos
        $stmt = $pdo->query("SHOW COLUMNS FROM cursos LIKE 'descricao'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE cursos ADD COLUMN descricao TEXT NULL AFTER nome");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Campo descricao adicionado na tabela cursos'];
        } else {
            $mensagens[] = ['tipo' => 'info', 'texto' => '• Campo descricao já existe'];
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM cursos LIKE 'carga_horaria'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE cursos ADD COLUMN carga_horaria INT DEFAULT 40 AFTER descricao");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Campo carga_horaria adicionado na tabela cursos'];
        } else {
            $mensagens[] = ['tipo' => 'info', 'texto' => '• Campo carga_horaria já existe'];
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM cursos LIKE 'tipo_periodo'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE cursos ADD COLUMN tipo_periodo ENUM('anual', 'semestral') DEFAULT 'semestral' AFTER carga_horaria");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Campo tipo_periodo adicionado na tabela cursos'];
        } else {
            $mensagens[] = ['tipo' => 'info', 'texto' => '• Campo tipo_periodo já existe'];
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM cursos LIKE 'capa'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE cursos ADD COLUMN capa VARCHAR(255) NULL AFTER tipo_periodo");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Campo capa adicionado na tabela cursos'];
        } else {
            $mensagens[] = ['tipo' => 'info', 'texto' => '• Campo capa já existe'];
        }
        
        // 2. Criar tabela turmas
        $stmt = $pdo->query("SHOW TABLES LIKE 'turmas'");
        if (!$stmt->fetch()) {
            $pdo->exec("
                CREATE TABLE turmas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    curso_id INT NOT NULL,
                    ano INT NOT NULL,
                    semestre TINYINT NULL COMMENT '1 ou 2 para semestral, NULL para anual',
                    data_inicio DATE NULL,
                    data_fim DATE NULL,
                    vagas INT DEFAULT 30,
                    status ENUM('ativa', 'fechada') DEFAULT 'ativa',
                    inscricoes_abertas TINYINT(1) DEFAULT 1,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
                    INDEX idx_curso (curso_id),
                    INDEX idx_ano (ano),
                    INDEX idx_status (status),
                    INDEX idx_inscricoes (inscricoes_abertas)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Tabela turmas criada'];
        } else {
            $mensagens[] = ['tipo' => 'info', 'texto' => '• Tabela turmas já existe'];
        }
        
        // 3. Criar tabela turma_instrutores
        $stmt = $pdo->query("SHOW TABLES LIKE 'turma_instrutores'");
        if (!$stmt->fetch()) {
            $pdo->exec("
                CREATE TABLE turma_instrutores (
                    turma_id INT NOT NULL,
                    instrutor_id INT NOT NULL,
                    PRIMARY KEY (turma_id, instrutor_id),
                    FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE,
                    FOREIGN KEY (instrutor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                    INDEX idx_turma (turma_id),
                    INDEX idx_instrutor (instrutor_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Tabela turma_instrutores criada'];
        } else {
            $mensagens[] = ['tipo' => 'info', 'texto' => '• Tabela turma_instrutores já existe'];
        }
        
        // 4. Migrar cursos existentes para turmas
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM turmas");
        $totalTurmas = $stmt->fetchColumn();
        
        if ($totalTurmas == 0) {
            // Buscar todos os cursos ativos
            $cursos = $pdo->query("SELECT * FROM cursos WHERE ativo = 1")->fetchAll();
            
            foreach ($cursos as $curso) {
                // Criar turma para cada curso existente
                $stmt = $pdo->prepare("
                    INSERT INTO turmas (curso_id, ano, semestre, status, inscricoes_abertas)
                    VALUES (?, ?, NULL, 'ativa', 1)
                ");
                $stmt->execute([$curso['id'], $curso['ano']]);
                $turmaId = $pdo->lastInsertId();
                
                // Migrar instrutores do curso para a turma
                $instrutores = $pdo->prepare("SELECT instrutor_id FROM curso_instrutores WHERE curso_id = ?");
                $instrutores->execute([$curso['id']]);
                
                $stmtInstrutor = $pdo->prepare("INSERT INTO turma_instrutores (turma_id, instrutor_id) VALUES (?, ?)");
                foreach ($instrutores->fetchAll() as $instrutor) {
                    $stmtInstrutor->execute([$turmaId, $instrutor['instrutor_id']]);
                }
            }
            
            $mensagens[] = ['tipo' => 'success', 'texto' => "✓ Migrados " . count($cursos) . " cursos para turmas"];
        } else {
            $mensagens[] = ['tipo' => 'info', 'texto' => "• Já existem $totalTurmas turmas no sistema"];
        }
        
        // 5. Adicionar campo turma_id nas tabelas relacionadas (se não existir)
        $tabelasParaAtualizar = ['matriculas', 'aulas', 'conclusoes'];
        
        foreach ($tabelasParaAtualizar as $tabela) {
            $stmt = $pdo->query("SHOW COLUMNS FROM $tabela LIKE 'turma_id'");
            if (!$stmt->fetch()) {
                $pdo->exec("ALTER TABLE $tabela ADD COLUMN turma_id INT NULL AFTER curso_id");
                $pdo->exec("ALTER TABLE $tabela ADD FOREIGN KEY (turma_id) REFERENCES turmas(id) ON DELETE CASCADE");
                $mensagens[] = ['tipo' => 'success', 'texto' => "✓ Campo turma_id adicionado em $tabela"];
                
                // Atualizar registros existentes
                $pdo->exec("
                    UPDATE $tabela t
                    INNER JOIN turmas tu ON t.curso_id = tu.curso_id
                    SET t.turma_id = tu.id
                    WHERE t.turma_id IS NULL
                ");
                $mensagens[] = ['tipo' => 'success', 'texto' => "✓ Registros de $tabela vinculados às turmas"];
            } else {
                $mensagens[] = ['tipo' => 'info', 'texto' => "• Campo turma_id já existe em $tabela"];
            }
        }
        
        $pdo->commit();
        $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Migração concluída com sucesso!'];
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $mensagens[] = ['tipo' => 'error', 'texto' => '✗ Erro: ' . $e->getMessage()];
    }
}

$pageTitle = 'Migração para Sistema de Turmas';
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Migração para Sistema de Turmas</h1>
    
    <?php if ($executado): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Resultado da Migração</h2>
            <div class="space-y-2">
                <?php foreach ($mensagens as $msg): ?>
                    <div class="flex items-start gap-2 p-3 rounded <?= $msg['tipo'] === 'success' ? 'bg-green-50 text-green-800' : ($msg['tipo'] === 'error' ? 'bg-red-50 text-red-800' : 'bg-blue-50 text-blue-800') ?>">
                        <?= $msg['texto'] ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-6 flex gap-4">
                <a href="/gestor/dashboard.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                    Ir para Dashboard
                </a>
                <a href="/gestor/turmas.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition">
                    Gerenciar Turmas
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">O que será feito?</h2>
            
            <div class="space-y-4 mb-6">
                <div class="border-l-4 border-blue-500 pl-4">
                    <h3 class="font-bold text-gray-800">1. Estrutura de Turmas</h3>
                    <p class="text-gray-600">Criação de tabelas para gerenciar turmas (ano/semestre) separadas dos cursos base</p>
                </div>
                
                <div class="border-l-4 border-green-500 pl-4">
                    <h3 class="font-bold text-gray-800">2. Controles de Status</h3>
                    <p class="text-gray-600">Adiciona controles de turma ativa/fechada e inscrições abertas/fechadas</p>
                </div>
                
                <div class="border-l-4 border-purple-500 pl-4">
                    <h3 class="font-bold text-gray-800">3. Migração de Dados</h3>
                    <p class="text-gray-600">Converte cursos existentes em turmas, preservando todos os dados</p>
                </div>
                
                <div class="border-l-4 border-orange-500 pl-4">
                    <h3 class="font-bold text-gray-800">4. Relacionamentos</h3>
                    <p class="text-gray-600">Atualiza matrículas, aulas e conclusões para usar turmas</p>
                </div>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-yellow-800 font-semibold">⚠️ Importante:</p>
                <ul class="list-disc list-inside text-yellow-700 mt-2 space-y-1">
                    <li>Faça backup do banco de dados antes de executar</li>
                    <li>A migração é irreversível</li>
                    <li>Todos os dados serão preservados</li>
                    <li>O sistema continuará funcionando normalmente</li>
                </ul>
            </div>
            
            <form method="POST">
                <button type="submit" name="executar" value="1" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg transition font-semibold">
                    Executar Migração
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
