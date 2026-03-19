<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 17:08:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole('gestor');

$pdo = getConnection();

$totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE ativo = 1")->fetchColumn();
$totalAlunos = $pdo->query("SELECT COUNT(*) FROM alunos WHERE ativo = 1")->fetchColumn();
$totalCursos = $pdo->query("SELECT COUNT(*) FROM cursos WHERE ativo = 1")->fetchColumn();
$totalMatriculas = $pdo->query("SELECT COUNT(*) FROM matriculas")->fetchColumn();

$pageTitle = 'Dashboard - Gestor';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-md p-6 border-t-4" style="border-color: #4e4483;">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm font-medium">Usuários</p>
                <p class="text-3xl font-bold" style="color: #4e4483;"><?= $totalUsuarios ?></p>
            </div>
            <div class="rounded-full p-3" style="background-color: rgba(78, 68, 131, 0.1);">
                <svg class="w-8 h-8" style="color: #4e4483;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 border-t-4" style="border-color: #89ab98;">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm font-medium">Alunos</p>
                <p class="text-3xl font-bold" style="color: #89ab98;"><?= $totalAlunos ?></p>
            </div>
            <div class="rounded-full p-3" style="background-color: rgba(137, 171, 152, 0.1);">
                <svg class="w-8 h-8" style="color: #89ab98;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 border-t-4" style="border-color: #6e9fcb;">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm font-medium">Cursos</p>
                <p class="text-3xl font-bold" style="color: #6e9fcb;"><?= $totalCursos ?></p>
            </div>
            <div class="rounded-full p-3" style="background-color: rgba(110, 159, 203, 0.1);">
                <svg class="w-8 h-8" style="color: #6e9fcb;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 border-t-4" style="border-color: #e79048;">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm font-medium">Matrículas</p>
                <p class="text-3xl font-bold" style="color: #e79048;"><?= $totalMatriculas ?></p>
            </div>
            <div class="rounded-full p-3" style="background-color: rgba(231, 144, 72, 0.1);">
                <svg class="w-8 h-8" style="color: #e79048;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Acesso Rápido</h2>
        <div class="space-y-3">
            <a href="/gestor/instrutores.php" class="block p-4 rounded-lg transition border-l-4 hover:shadow-md" style="background-color: rgba(78, 68, 131, 0.1); border-color: #4e4483;">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 flex-shrink-0 mt-0.5" style="color: #4e4483;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <div>
                        <h3 class="font-semibold" style="color: #4e4483;">Gerenciar Instrutores</h3>
                        <p class="text-sm text-gray-600">Criar, editar e visualizar instrutores</p>
                    </div>
                </div>
            </a>
            <a href="/gestor/alunos.php" class="block p-4 rounded-lg transition border-l-4 hover:shadow-md" style="background-color: rgba(137, 171, 152, 0.1); border-color: #89ab98;">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 flex-shrink-0 mt-0.5" style="color: #89ab98;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <div>
                        <h3 class="font-semibold" style="color: #89ab98;">Gerenciar Alunos</h3>
                        <p class="text-sm text-gray-600">Cadastrar e gerenciar alunos</p>
                    </div>
                </div>
            </a>
            <div class="rounded-lg border-l-4 overflow-hidden" style="border-color: #4e4483;">
                <a href="/gestor/cursos.php" class="block p-4 transition hover:shadow-md" style="background-color: rgba(78, 68, 131, 0.1);">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 flex-shrink-0 mt-0.5" style="color: #4e4483;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <div>
                            <h3 class="font-semibold" style="color: #4e4483;">Criar e Gerenciar Cursos</h3>
                            <p class="text-sm text-gray-600">Criar e administrar cursos base</p>
                        </div>
                    </div>
                </a>
                <div class="pl-8 border-t" style="border-color: rgba(78, 68, 131, 0.15);">
                    <a href="/gestor/turmas.php" class="flex items-center gap-2 px-4 py-3 transition hover:bg-white" style="background-color: rgba(78, 68, 131, 0.05);">
                        <svg class="w-4 h-4 flex-shrink-0" style="color: #6e9fcb;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <div>
                            <span class="text-sm font-medium" style="color: #6e9fcb;">Gerenciar Turmas</span>
                            <span class="text-xs text-gray-500 ml-1">- Ano/semestre e inscricoes</span>
                        </div>
                    </a>
                    <a href="/diretor/matriculas_kanban.php" class="flex items-center gap-2 px-4 py-3 transition hover:bg-white border-t" style="background-color: rgba(78, 68, 131, 0.05); border-color: rgba(78, 68, 131, 0.1);">
                        <svg class="w-4 h-4 flex-shrink-0" style="color: #6e9fcb;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path>
                        </svg>
                        <div>
                            <span class="text-sm font-medium" style="color: #6e9fcb;">Vincular Alunos a Turmas</span>
                            <span class="text-xs text-gray-500 ml-1">- Kanban</span>
                        </div>
                    </a>
                </div>
            </div>
            <div class="rounded-lg border-l-4 overflow-hidden" style="border-color: #e79048;">
                <a href="/instrutor/nova_chamada.php" class="block p-4 transition hover:shadow-md" style="background-color: rgba(231, 144, 72, 0.1);">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 flex-shrink-0 mt-0.5" style="color: #e79048;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                        <div>
                            <h3 class="font-semibold" style="color: #e79048;">Registrar Chamada</h3>
                            <p class="text-sm text-gray-600">Fazer chamada e registrar presença dos alunos</p>
                        </div>
                    </div>
                </a>
                <div class="pl-8 border-t" style="border-color: rgba(231, 144, 72, 0.15);">
                    <a href="/gestor/selecionar_curso_historico.php" class="flex items-center gap-2 px-4 py-3 transition hover:bg-white" style="background-color: rgba(231, 144, 72, 0.05);">
                        <svg class="w-4 h-4 flex-shrink-0" style="color: #e79048;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <span class="text-sm font-medium" style="color: #e79048;">Histórico de Chamadas</span>
                            <span class="text-xs text-gray-500 ml-1">- Por curso/turma</span>
                        </div>
                    </a>
                    <a href="/gestor/frequencia_alunos.php" class="flex items-center gap-2 px-4 py-3 transition hover:bg-white border-t" style="background-color: rgba(231, 144, 72, 0.05); border-color: rgba(231, 144, 72, 0.1);">
                        <svg class="w-4 h-4 flex-shrink-0" style="color: #e79048;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <div>
                            <span class="text-sm font-medium" style="color: #e79048;">Frequência dos Alunos</span>
                            <span class="text-xs text-gray-500 ml-1">- Individual</span>
                        </div>
                    </a>
                </div>
            </div>
            <a href="/gestor/relatorios.php" class="block p-4 rounded-lg transition border-l-4 hover:shadow-md" style="background-color: rgba(231, 144, 72, 0.1); border-color: #e79048;">
                <div class="flex items-start gap-3">
                    <svg class="w-6 h-6 flex-shrink-0 mt-0.5" style="color: #e79048;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <div>
                        <h3 class="font-semibold" style="color: #e79048;">Relatórios</h3>
                        <p class="text-sm text-gray-600">Visualizar relatórios e estatísticas</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
    
    <div class="space-y-6">
        <!-- Botão Página Pública -->
        <a href="/index.php" target="_blank" class="block bg-gradient-to-r text-white rounded-lg shadow-md p-5 hover:shadow-xl transition group" style="background: linear-gradient(135deg, #4e4483 0%, #6e9fcb 100%);">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-lg">Página Pública</h3>
                    <p class="text-sm opacity-90 mt-1">Ver como os alunos veem o portal</p>
                </div>
                <svg class="w-8 h-8 opacity-80 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
            </div>
        </a>

        <!-- Turmas Ativas -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Turmas Ativas</h2>
            <?php
            $turmasAtivas = $pdo->query("
                SELECT t.id, t.ano, t.semestre, t.status,
                       c.nome as curso_nome,
                       COUNT(DISTINCT m.aluno_id) as total_alunos,
                       COUNT(DISTINCT au.id) as total_aulas,
                       GROUP_CONCAT(DISTINCT u.nome ORDER BY u.nome SEPARATOR ', ') as instrutores
                FROM turmas t
                INNER JOIN cursos c ON t.curso_id = c.id
                LEFT JOIN matriculas m ON m.turma_id = t.id
                LEFT JOIN aulas au ON (au.turma_id = t.id OR (au.turma_id IS NULL AND au.curso_id = t.curso_id))
                LEFT JOIN turma_instrutores ti ON t.id = ti.turma_id
                LEFT JOIN usuarios u ON ti.instrutor_id = u.id
                WHERE t.status = 'ativa' AND c.ativo = 1
                GROUP BY t.id
                ORDER BY t.ano DESC, c.nome
            ")->fetchAll();

            if ($turmasAtivas):
            ?>
                <div class="space-y-3">
                    <?php foreach ($turmasAtivas as $turma): ?>
                        <a href="/instrutor/historico_chamadas.php?turma_id=<?= $turma['id'] ?>" class="block border border-gray-200 rounded-lg p-4 hover:bg-gray-50 hover:shadow-sm transition">
                            <div class="flex justify-between items-start">
                                <h3 class="font-semibold text-gray-900 text-sm"><?= htmlspecialchars($turma['curso_nome']) ?></h3>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-semibold"><?= $turma['ano'] ?></span>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    <?= $turma['instrutores'] ?: '<span class="text-orange-500">Sem instrutor</span>' ?>
                                </span>
                            </div>
                            <div class="mt-2 flex gap-3">
                                <span class="text-xs px-2 py-1 rounded-full bg-blue-50 text-blue-700 font-medium"><?= $turma['total_alunos'] ?> alunos</span>
                                <span class="text-xs px-2 py-1 rounded-full bg-purple-50 text-purple-700 font-medium"><?= $turma['total_aulas'] ?> aulas</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-sm italic">Nenhuma turma ativa no momento.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
