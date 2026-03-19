<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-14 15:17:00
 * 
 * Página de inscrição em curso
 */

require_once __DIR__ . '/../config/database.php';

$pdo = getConnection();

$turmaId = isset($_GET['turma']) ? (int)$_GET['turma'] : 0;

if (!$turmaId) {
    header('Location: /');
    exit;
}

$turma = $pdo->prepare("
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
    WHERE t.id = ? AND c.ativo = 1 AND t.status = 'ativa' AND t.inscricoes_abertas = 1
    GROUP BY t.id, t.curso_id, t.ano, t.semestre, t.data_inicio, t.data_fim, t.vagas, t.modalidade, c.nome, c.descricao, c.pre_requisito, c.carga_horaria, c.tipo_periodo, c.capa
");
$turma->execute([$turmaId]);
$turma = $turma->fetch();

if (!$turma) {
    header('Location: /');
    exit;
}

$periodo = $turma['ano'];
if ($turma['semestre']) {
    $periodo .= ' - ' . $turma['semestre'] . 'º Semestre';
}

$vagasDisponiveis = $turma['vagas'] - $turma['total_inscritos'];

if ($vagasDisponiveis <= 0) {
    header('Location: /');
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscrição - <?= htmlspecialchars($turma['curso_nome']) ?> - CEMA</title>
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
    <header class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold" style="color: var(--cema-purple);">CEMA</h1>
                    <p class="text-sm text-gray-600">Centro Espírita Maria de Magdala</p>
                </div>
                <nav class="flex gap-6">
                    <a href="/" class="text-gray-700 hover:text-purple-600 font-medium transition">← Voltar aos Cursos</a>
                    <a href="/login.php" class="text-gray-700 hover:text-purple-600 font-medium transition">Área Restrita</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-5xl mx-auto px-4 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Informações do Curso -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">
                    <?= htmlspecialchars($turma['curso_nome']) ?>
                </h2>
                
                <div class="mb-6">
                    <span class="px-3 py-1 text-sm font-semibold rounded-full <?= $turma['tipo_periodo'] === 'anual' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                        <?= ucfirst($turma['tipo_periodo']) ?>
                    </span>
                    <span class="ml-2 text-gray-600"><?= htmlspecialchars($periodo) ?></span>
                </div>
                
                <?php if ($turma['capa']): ?>
                    <div class="mb-6 rounded-lg overflow-hidden cursor-pointer" onclick="abrirLightbox('/assets/uploads/<?= htmlspecialchars($turma['capa']) ?>')">
                        <img src="/assets/uploads/<?= htmlspecialchars($turma['capa']) ?>" 
                             alt="<?= htmlspecialchars($turma['curso_nome']) ?>"
                             class="w-full max-w-sm mx-auto object-contain hover:opacity-90 transition">
                    </div>
                <?php endif; ?>
                
                <?php if ($turma['curso_descricao']): ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Sobre o Curso</h3>
                        <p class="text-gray-600"><?= nl2br(htmlspecialchars($turma['curso_descricao'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if ($turma['pre_requisito']): ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Pré-requisito</h3>
                        <p class="text-gray-600"><?= nl2br(htmlspecialchars($turma['pre_requisito'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="space-y-3">
                    <?php
                    $diasSemanaMap = [
                        'segunda' => 'segundas',
                        'terca' => 'terças',
                        'quarta' => 'quartas',
                        'quinta' => 'quintas',
                        'sexta' => 'sextas',
                        'sabado' => 'sábados',
                        'domingo' => 'domingos'
                    ];
                    
                    $diasSemana = null;
                    if (!empty($turma['dias_semana'])) {
                        $dias = json_decode($turma['dias_semana'], true);
                        if ($dias && is_array($dias)) {
                            $diasFormatados = array_map(function($dia) use ($diasSemanaMap) {
                                return $diasSemanaMap[$dia] ?? $dia;
                            }, $dias);
                            
                            if (count($diasFormatados) == 1) {
                                $diasSemana = 'Todas as ' . $diasFormatados[0];
                            } elseif (count($diasFormatados) == 2) {
                                $diasSemana = 'Todas as ' . implode(' e ', $diasFormatados);
                            } else {
                                $ultimo = array_pop($diasFormatados);
                                $diasSemana = 'Todas as ' . implode(', ', $diasFormatados) . ' e ' . $ultimo;
                            }
                        }
                    }
                    
                    $horario = null;
                    if (!empty($turma['horario_inicio']) && !empty($turma['horario_fim'])) {
                        $horario = 'das ' . date('H\hi', strtotime($turma['horario_inicio'])) . ' às ' . date('H\hi', strtotime($turma['horario_fim']));
                    }
                    ?>
                    
                    <?php if ($diasSemana): ?>
                        <div class="flex items-start text-gray-700">
                            <svg class="w-6 h-6 mr-3 mt-0.5 text-purple-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span><strong>Dias:</strong> <?= $diasSemana ?><?= $horario ? ', ' . $horario : '' ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex items-center text-gray-700">
                        <svg class="w-6 h-6 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span><strong>Carga horária:</strong> <?= $turma['carga_horaria'] ?>h</span>
                    </div>
                    
                    <div class="flex items-center text-gray-700">
                        <svg class="w-6 h-6 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path>
                        </svg>
                        <span><strong>Modalidade:</strong> <?= ucfirst($turma['modalidade']) ?></span>
                    </div>
                    
                    <?php if ($turma['data_inicio'] && $turma['data_fim']): ?>
                        <div class="flex items-center text-gray-700">
                            <svg class="w-6 h-6 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span><strong>Período:</strong> <?= date('d/m/Y', strtotime($turma['data_inicio'])) ?> até <?= date('d/m/Y', strtotime($turma['data_fim'])) ?></span>
                        </div>
                    <?php elseif ($turma['data_inicio']): ?>
                        <div class="flex items-center text-gray-700">
                            <svg class="w-6 h-6 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span><strong>Início:</strong> <?= date('d/m/Y', strtotime($turma['data_inicio'])) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($turma['instrutores']): ?>
                        <div class="flex items-center text-gray-700">
                            <svg class="w-6 h-6 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span><strong>Instrutores:</strong> <?= htmlspecialchars($turma['instrutores']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>
            
            <!-- Formulário de Inscrição -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h3 class="text-2xl font-bold text-gray-800 mb-6">Formulário de Inscrição</h3>
                
                <div id="mensagem" class="hidden mb-4"></div>
                
                <form id="formInscricao" class="space-y-6">
                    <input type="hidden" name="turma_id" value="<?= $turmaId ?>">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Nome Completo *
                        </label>
                        <input type="text" name="nome" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                               placeholder="Digite seu nome completo">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            WhatsApp *
                        </label>
                        <input type="tel" name="whatsapp" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                               placeholder="(00) 00000-0000"
                               maxlength="15">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            E-mail
                        </label>
                        <input type="email" name="email" 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                               placeholder="seu@email.com">
                        <p class="text-xs text-gray-500 mt-1">Opcional</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Data de Nascimento *
                        </label>
                        <input type="date" name="data_nascimento" required 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Endereço
                        </label>
                        <textarea name="endereco" rows="3"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                  placeholder="Rua, número, bairro, cidade"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Opcional</p>
                    </div>
                    
                    <button type="submit" id="btnSubmit"
                            class="w-full bg-purple-600 hover:bg-purple-700 text-white py-4 rounded-lg transition font-semibold text-lg">
                        Confirmar Inscrição
                    </button>
                    
                    <p class="text-xs text-gray-500 text-center">
                        * Campos obrigatórios
                    </p>
                </form>
            </div>
            
        </div>
    </div>

    <script>
    // Máscara de WhatsApp
    document.querySelector('input[name="whatsapp"]').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 11) value = value.slice(0, 11);
        
        if (value.length > 10) {
            value = value.replace(/^(\d{2})(\d{5})(\d{4}).*/, '($1) $2-$3');
        } else if (value.length > 6) {
            value = value.replace(/^(\d{2})(\d{4})(\d{0,4}).*/, '($1) $2-$3');
        } else if (value.length > 2) {
            value = value.replace(/^(\d{2})(\d{0,5})/, '($1) $2');
        } else {
            value = value.replace(/^(\d*)/, '($1');
        }
        
        e.target.value = value;
    });
    
    // Envio do formulário
    document.getElementById('formInscricao').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const btnSubmit = document.getElementById('btnSubmit');
        const mensagem = document.getElementById('mensagem');
        
        btnSubmit.disabled = true;
        btnSubmit.textContent = 'Processando...';
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('/processar_inscricao.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            mensagem.className = 'p-4 rounded-lg mb-4 ' + (result.success ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800');
            mensagem.textContent = result.message;
            mensagem.classList.remove('hidden');
            
            if (result.success) {
                this.reset();
                setTimeout(() => {
                    window.location.href = '/';
                }, 3000);
            } else {
                btnSubmit.disabled = false;
                btnSubmit.textContent = 'Confirmar Inscrição';
            }
        } catch (error) {
            mensagem.className = 'p-4 rounded-lg mb-4 bg-red-100 text-red-800';
            mensagem.textContent = 'Erro ao processar inscrição. Tente novamente.';
            mensagem.classList.remove('hidden');
            
            btnSubmit.disabled = false;
            btnSubmit.textContent = 'Confirmar Inscrição';
        }
    });
    
    // Lightbox
    function abrirLightbox(imagemUrl) {
        const lightbox = document.createElement('div');
        lightbox.id = 'lightbox';
        lightbox.className = 'fixed inset-0 bg-black bg-opacity-90 z-50 flex items-center justify-center p-4';
        lightbox.onclick = function() { this.remove(); };
        
        const img = document.createElement('img');
        img.src = imagemUrl;
        img.className = 'max-w-full max-h-full object-contain';
        img.onclick = function(e) { e.stopPropagation(); };
        
        lightbox.appendChild(img);
        document.body.appendChild(lightbox);
    }
    </script>

</body>
</html>
