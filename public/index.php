<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-14 15:17:00
 * 
 * Página pública - Landing page de cursos disponíveis
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getConnection();

$turmasDisponiveis = $pdo->query("
    SELECT 
        t.*,
        c.nome as curso_nome,
        c.descricao as curso_descricao,
        c.pre_requisito,
        c.carga_horaria,
        c.tipo_periodo,
        c.capa,
        COUNT(DISTINCT m.aluno_id) as total_inscritos,
        GROUP_CONCAT(DISTINCT u.nome ORDER BY u.nome SEPARATOR ', ') as instrutores
    FROM turmas t
    INNER JOIN cursos c ON t.curso_id = c.id
    LEFT JOIN matriculas m ON t.id = m.turma_id
    LEFT JOIN turma_instrutores ti ON t.id = ti.turma_id
    LEFT JOIN usuarios u ON ti.instrutor_id = u.id
    WHERE c.ativo = 1 
        AND t.status = 'ativa' 
        AND t.inscricoes_abertas = 1
    GROUP BY t.id, t.curso_id, t.ano, t.semestre, t.data_inicio, t.data_fim, t.vagas, t.modalidade, c.nome, c.descricao, c.pre_requisito, c.carga_horaria, c.tipo_periodo, c.capa
    ORDER BY t.data_inicio ASC, c.nome
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursos Disponíveis - CEMA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --cema-orange: #e79048;
            --cema-green: #89ab98;
            --cema-blue: #6e9fcb;
            --cema-purple: #4e4483;
            --cema-beige: #f3eddd;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-purple-50 to-blue-50 min-h-screen">
    
    <!-- Header -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold" style="color: var(--cema-purple);">CEMA</h1>
                    <p class="text-sm text-gray-600">Centro Espírita Maria de Magdala</p>
                </div>
                <nav class="flex gap-6">
                    <a href="/" class="text-gray-700 hover:text-purple-600 font-medium transition">Cursos</a>
                    <a href="/login.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition font-medium">
                        Área Restrita
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="py-16 px-4">
        <div class="max-w-7xl mx-auto text-center">
            <h2 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">
                Cursos Disponíveis para Inscrição
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Explore nossos cursos de estudo espírita e inscreva-se gratuitamente. 
                Todos são bem-vindos para aprender e crescer conosco.
            </p>
        </div>
    </section>

    <!-- Cursos Grid -->
    <section class="pb-16 px-4">
        <div class="max-w-7xl mx-auto">
            <?php if (empty($turmasDisponiveis)): ?>
                <div class="bg-white rounded-lg shadow-md p-12 text-center">
                    <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <h3 class="text-2xl font-bold text-gray-800 mb-2">Nenhum curso disponível no momento</h3>
                    <p class="text-gray-600">Novas turmas serão abertas em breve. Volte mais tarde!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <?php foreach ($turmasDisponiveis as $turma): 
                        $periodo = $turma['ano'];
                        if ($turma['semestre']) {
                            $periodo .= ' - ' . $turma['semestre'] . 'º Semestre';
                        }
                        
                        $vagasDisponiveis = $turma['vagas'] - $turma['total_inscritos'];
                        $percentualOcupacao = $turma['vagas'] > 0 ? round(($turma['total_inscritos'] / $turma['vagas']) * 100) : 0;
                    ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition transform hover:-translate-y-1">
                        <!-- Capa do Curso (Clicável) -->
                        <a href="/inscricao.php?turma=<?= $turma['id'] ?>" class="block">
                            <?php if ($turma['capa']): ?>
                                <div class="h-80 overflow-hidden flex items-center justify-center bg-gray-100 cursor-pointer">
                                    <img src="/assets/uploads/<?= htmlspecialchars($turma['capa']) ?>" 
                                         alt="<?= htmlspecialchars($turma['curso_nome']) ?>"
                                         class="h-full w-auto object-contain">
                                </div>
                            <?php else: ?>
                                <div class="h-80 bg-gradient-to-br from-purple-400 to-blue-500 flex items-center justify-center cursor-pointer">
                                    <svg class="w-24 h-24 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </a>
                        
                        <!-- Conteúdo -->
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-2">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full <?= $turma['tipo_periodo'] === 'anual' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                                    <?= ucfirst($turma['tipo_periodo']) ?>
                                </span>
                                <?php if ($vagasDisponiveis <= 5 && $vagasDisponiveis > 0): ?>
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">
                                        Últimas vagas!
                                    </span>
                                <?php elseif ($vagasDisponiveis == 0): ?>
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                        Esgotado
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="text-2xl font-bold text-gray-800 mb-2">
                                <?= htmlspecialchars($turma['curso_nome']) ?>
                            </h3>
                            
                            <p class="text-sm text-gray-600 mb-4">
                                <?= htmlspecialchars($periodo) ?>
                            </p>
                            
                            <?php if ($turma['curso_descricao']): ?>
                                <p class="text-gray-600 mb-4 line-clamp-3">
                                    <?= htmlspecialchars($turma['curso_descricao']) ?>
                                </p>
                            <?php endif; ?>
                            
                            <!-- Informações -->
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Carga horária: <?= $turma['carga_horaria'] ?>h
                                </div>
                                
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                                    </svg>
                                    Modalidade: <?= ucfirst($turma['modalidade']) ?>
                                </div>
                                
                                <?php if ($turma['data_inicio'] && $turma['data_fim']): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        Período: <?= date('d/m/Y', strtotime($turma['data_inicio'])) ?> - <?= date('d/m/Y', strtotime($turma['data_fim'])) ?>
                                    </div>
                                <?php elseif ($turma['data_inicio']): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        Início: <?= date('d/m/Y', strtotime($turma['data_inicio'])) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($turma['instrutores']): ?>
                                    <div class="flex items-center text-sm text-gray-600">
                                        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <?= htmlspecialchars($turma['instrutores']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Barra de Progresso -->
                            <div class="mb-4">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full <?= $percentualOcupacao >= 90 ? 'bg-red-500' : ($percentualOcupacao >= 70 ? 'bg-orange-500' : 'bg-green-500') ?>" 
                                         style="width: <?= $percentualOcupacao ?>%"></div>
                                </div>
                            </div>
                            
                            <!-- Botão de Inscrição -->
                            <?php if ($vagasDisponiveis > 0): ?>
                                <a href="/inscricao.php?turma=<?= $turma['id'] ?>" 
                                   class="block w-full bg-purple-600 hover:bg-purple-700 text-white text-center py-3 rounded-lg transition font-semibold">
                                    Inscrever-se Agora
                                </a>
                            <?php else: ?>
                                <button disabled class="block w-full bg-gray-300 text-gray-500 text-center py-3 rounded-lg font-semibold cursor-not-allowed">
                                    Vagas Esgotadas
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-8">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-600">
                © <?= date('Y') ?> CEMA - Centro Espírita Maria de Magdala
            </p>
            <p class="text-sm text-gray-500 mt-2">
                Todos os direitos reservados
            </p>
        </div>
    </footer>

</body>
</html>
