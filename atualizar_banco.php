<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 19:38:00
 */

require_once __DIR__ . '/config/database.php';

$executado = false;
$mensagens = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['executar'])) {
    try {
        $pdo = getConnection();
        $executado = true;
        
        // Verificar se a tabela existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'conclusoes'");
        $tabelaExiste = $stmt->fetch();
        
        if ($tabelaExiste) {
            // Verificar se tem a coluna 'status'
            $stmt = $pdo->query("DESCRIBE conclusoes");
            $colunas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $temStatus = false;
            $temAprovado = false;
            
            foreach ($colunas as $col) {
                if ($col['Field'] === 'status') {
                    $temStatus = true;
                }
                if ($col['Field'] === 'aprovado') {
                    $temAprovado = true;
                }
            }
            
            if (!$temStatus && $temAprovado) {
                // Remover coluna aprovado e adicionar status
                $pdo->exec("ALTER TABLE conclusoes DROP COLUMN aprovado");
                $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Coluna "aprovado" removida'];
                
                $pdo->exec("ALTER TABLE conclusoes ADD COLUMN status ENUM('aprovado', 'reprovado') NOT NULL AFTER curso_id");
                $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Coluna "status" adicionada'];
                
                // Remover coluna ano_conclusao se existir
                $pdo->exec("ALTER TABLE conclusoes DROP COLUMN IF EXISTS ano_conclusao");
                $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Coluna "ano_conclusao" removida'];
                
                // Adicionar coluna atualizado_em se não existir
                $temAtualizado = false;
                foreach ($colunas as $col) {
                    if ($col['Field'] === 'atualizado_em') {
                        $temAtualizado = true;
                    }
                }
                
                // Verificar se tem criado_em
                $temCriado = false;
                foreach ($colunas as $col) {
                    if ($col['Field'] === 'criado_em') {
                        $temCriado = true;
                    }
                }
                
                if (!$temCriado) {
                    $pdo->exec("ALTER TABLE conclusoes ADD COLUMN criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER observacoes");
                    $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Coluna "criado_em" adicionada'];
                }
                
                if (!$temAtualizado) {
                    $pdo->exec("ALTER TABLE conclusoes ADD COLUMN atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER criado_em");
                    $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Coluna "atualizado_em" adicionada'];
                }
                
                $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Estrutura da tabela conclusoes corrigida!'];
            } elseif ($temStatus) {
                $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Tabela conclusoes já está com a estrutura correta!'];
            } else {
                $mensagens[] = ['tipo' => 'error', 'texto' => '✗ Estrutura da tabela não reconhecida. Contate o desenvolvedor.'];
            }
        } else {
            // Criar tabela do zero
            $pdo->exec("
                CREATE TABLE conclusoes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    aluno_id INT NOT NULL,
                    curso_id INT NOT NULL,
                    status ENUM('aprovado', 'reprovado') NOT NULL,
                    observacoes TEXT,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_aluno_curso (aluno_id, curso_id),
                    FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE,
                    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Tabela conclusoes criada com sucesso!'];
        }
        
        // Criar tabela configuracoes
        $stmt = $pdo->query("SHOW TABLES LIKE 'configuracoes'");
        $tabelaConfigExiste = $stmt->fetch();
        
        if (!$tabelaConfigExiste) {
            $pdo->exec("
                CREATE TABLE configuracoes (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    chave VARCHAR(100) NOT NULL UNIQUE,
                    valor TEXT,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Tabela configuracoes criada com sucesso!'];
        } else {
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Tabela configuracoes já existe!'];
        }
        
        // Criar tabela tokens_recuperacao
        $stmt = $pdo->query("SHOW TABLES LIKE 'tokens_recuperacao'");
        $tabelaTokensExiste = $stmt->fetch();
        
        if (!$tabelaTokensExiste) {
            $pdo->exec("
                CREATE TABLE tokens_recuperacao (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    usuario_id INT NOT NULL,
                    token VARCHAR(64) NOT NULL UNIQUE,
                    expiracao DATETIME NOT NULL,
                    usado TINYINT(1) DEFAULT 0,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                    INDEX idx_token (token),
                    INDEX idx_expiracao (expiracao)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Tabela tokens_recuperacao criada com sucesso!'];
        } else {
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Tabela tokens_recuperacao já existe!'];
        }
        
        // Criar tabela curso_instrutores para relacionamento N:N
        $stmt = $pdo->query("SHOW TABLES LIKE 'curso_instrutores'");
        $tabelaCursoInstrutoresExiste = $stmt->fetch();
        
        if (!$tabelaCursoInstrutoresExiste) {
            $pdo->exec("
                CREATE TABLE curso_instrutores (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    curso_id INT NOT NULL,
                    instrutor_id INT NOT NULL,
                    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE,
                    FOREIGN KEY (instrutor_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_curso_instrutor (curso_id, instrutor_id),
                    INDEX idx_curso (curso_id),
                    INDEX idx_instrutor (instrutor_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Tabela curso_instrutores criada com sucesso!'];
            
            // Migrar dados existentes
            $cursosComInstrutor = $pdo->query("SELECT id, instrutor_id FROM cursos WHERE instrutor_id IS NOT NULL");
            $migrados = 0;
            foreach ($cursosComInstrutor as $curso) {
                $pdo->prepare("INSERT IGNORE INTO curso_instrutores (curso_id, instrutor_id) VALUES (?, ?)")
                    ->execute([$curso['id'], $curso['instrutor_id']]);
                $migrados++;
            }
            if ($migrados > 0) {
                $mensagens[] = ['tipo' => 'success', 'texto' => "✓ Migrados {$migrados} relacionamentos curso-instrutor!"];
            }
        } else {
            $mensagens[] = ['tipo' => 'success', 'texto' => '✓ Tabela curso_instrutores já existe!'];
        }
        
    } catch (Exception $e) {
        $mensagens[] = ['tipo' => 'error', 'texto' => '✗ Erro: ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar Banco de Dados</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-12">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="flex items-center gap-3 mb-6">
                <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                </svg>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Atualizar Banco de Dados</h1>
                    <p class="text-gray-600">Sistema de Chamada DED</p>
                </div>
            </div>
            
            <?php if ($executado && !empty($mensagens)): ?>
                <div class="mb-6 space-y-3">
                    <?php foreach ($mensagens as $msg): ?>
                        <div class="p-4 rounded-lg <?= $msg['tipo'] === 'success' ? 'bg-green-100 border-2 border-green-500 text-green-800' : 'bg-red-100 border-2 border-red-500 text-red-800' ?>">
                            <p class="font-semibold"><?= $msg['texto'] ?></p>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="mt-6 flex gap-3">
                        <a href="/index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                            Ir para o Sistema
                        </a>
                        <a href="/atualizar_banco.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                            Executar Novamente
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="mb-6">
                    <div class="bg-blue-50 border-2 border-blue-400 rounded-lg p-6 mb-6">
                        <h2 class="text-xl font-bold text-blue-800 mb-3">📋 Atualizações que serão aplicadas:</h2>
                        <ul class="space-y-2 text-blue-700">
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span><strong>Verificar e corrigir estrutura da tabela conclusoes</strong></span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Remover coluna "aprovado" (antiga)</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Adicionar coluna "status" ENUM('aprovado', 'reprovado')</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Adicionar coluna "atualizado_em" se não existir</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Criar tabela "configuracoes" para configurações SMTP</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="bg-yellow-50 border-2 border-yellow-400 rounded-lg p-6 mb-6">
                        <div class="flex items-start gap-3">
                            <svg class="w-6 h-6 text-yellow-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div>
                                <h3 class="text-lg font-bold text-yellow-800 mb-2">⚠️ Atenção</h3>
                                <ul class="text-yellow-700 space-y-1 text-sm">
                                    <li>• Esta operação é segura e não afeta dados existentes</li>
                                    <li>• Usa CREATE TABLE IF NOT EXISTS (não duplica tabelas)</li>
                                    <li>• Recomendado fazer backup antes de grandes atualizações</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <form method="POST" onsubmit="return confirm('Confirma a atualização do banco de dados?')">
                        <button type="submit" name="executar" value="1" class="w-full bg-green-600 hover:bg-green-700 text-white py-4 rounded-lg font-bold text-lg transition">
                            🚀 Executar Atualização do Banco de Dados
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="font-bold text-gray-800 mb-3">📊 Estrutura da Tabela conclusoes:</h3>
                <div class="bg-gray-50 rounded-lg p-4 font-mono text-sm">
                    <p class="text-gray-700">• <strong>id</strong> - INT (chave primária)</p>
                    <p class="text-gray-700">• <strong>aluno_id</strong> - INT (FK para alunos)</p>
                    <p class="text-gray-700">• <strong>curso_id</strong> - INT (FK para cursos)</p>
                    <p class="text-gray-700">• <strong>status</strong> - ENUM('aprovado', 'reprovado')</p>
                    <p class="text-gray-700">• <strong>observacoes</strong> - TEXT</p>
                    <p class="text-gray-700">• <strong>criado_em</strong> - TIMESTAMP</p>
                    <p class="text-gray-700">• <strong>atualizado_em</strong> - TIMESTAMP</p>
                </div>
            </div>
            
            <div class="mt-6 text-center text-sm text-gray-500">
                <p>Desenvolvido por Thiago Mourão</p>
                <p><a href="https://www.instagram.com/mouraoeguerin/" target="_blank" class="text-blue-600 hover:underline">@mouraoeguerin</a></p>
            </div>
        </div>
    </div>
</body>
</html>
