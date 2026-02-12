<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 18:32:00
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['gestor', 'diretor']);

$pdo = getConnection();

$alunos = $pdo->query("SELECT id, nome, foto FROM alunos WHERE ativo = 1 ORDER BY nome")->fetchAll();
$cursos = $pdo->query("SELECT id, nome, ano, capa FROM cursos WHERE ativo = 1 ORDER BY ano DESC, nome")->fetchAll();

$matriculasExistentes = $pdo->query("
    SELECT aluno_id, curso_id 
    FROM matriculas m
    INNER JOIN alunos a ON m.aluno_id = a.id
    INNER JOIN cursos c ON m.curso_id = c.id
    WHERE a.ativo = 1 AND c.ativo = 1
")->fetchAll(PDO::FETCH_KEY_PAIR);

$alunosMatriculados = array_keys($matriculasExistentes);
$alunosSemCurso = array_filter($alunos, function($aluno) use ($alunosMatriculados) {
    return !in_array($aluno['id'], $alunosMatriculados);
});

$pageTitle = 'Vincular Alunos aos Cursos';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
.kanban-container {
    display: flex;
    gap: 1.5rem;
    overflow-x: auto;
    padding-bottom: 1rem;
}

.kanban-column {
    min-width: 300px;
    max-width: 350px;
    flex-shrink: 0;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.kanban-header {
    padding: 1rem;
    border-bottom: 2px solid #e5e7eb;
    position: sticky;
    top: 0;
    border-radius: 12px 12px 0 0;
    z-index: 10;
}

.kanban-body {
    padding: 1rem;
    min-height: 400px;
    max-height: calc(100vh - 300px);
    overflow-y: auto;
}

.aluno-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.75rem;
    margin-bottom: 0.75rem;
    cursor: move;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.aluno-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
    transform: translateY(-2px);
}

.aluno-card.dragging {
    opacity: 0.5;
    transform: rotate(5deg);
}

.kanban-body.drag-over {
    background: #eff6ff;
    border: 2px dashed #3b82f6;
    border-radius: 8px;
}

.aluno-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.aluno-avatar-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
    flex-shrink: 0;
}

.curso-capa {
    width: 60px;
    height: 75px;
    object-fit: cover;
    border-radius: 6px;
    margin-right: 0.75rem;
}

.curso-capa-placeholder {
    width: 60px;
    height: 75px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0.75rem;
}

.empty-state {
    text-align: center;
    padding: 2rem 1rem;
    color: #9ca3af;
}

.badge-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    padding: 0 8px;
    background: #3b82f6;
    color: white;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}
</style>

<div class="mb-6">
    <div class="flex justify-between items-start">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Vincular Alunos aos Cursos</h1>
            <p class="text-gray-600 mt-2">Arraste os alunos para os cursos desejados</p>
        </div>
        <a href="/diretor/matriculas.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
            </svg>
            Ver Lista
        </a>
    </div>
</div>

<div class="kanban-container">
    <!-- Coluna de Alunos Sem Curso -->
    <div class="kanban-column">
        <div class="kanban-header bg-gradient-to-r from-gray-600 to-gray-700 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h3 class="font-bold text-white">Alunos Disponíveis</h3>
                </div>
                <span class="badge-count"><?= count($alunosSemCurso) ?></span>
            </div>
        </div>
        <div class="kanban-body" data-curso-id="0">
            <?php if (empty($alunosSemCurso)): ?>
                <div class="empty-state">
                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm">Todos os alunos estão matriculados!</p>
                </div>
            <?php else: ?>
                <?php foreach ($alunosSemCurso as $aluno): ?>
                    <div class="aluno-card" draggable="true" data-aluno-id="<?= $aluno['id'] ?>">
                        <?php if ($aluno['foto']): ?>
                            <img src="/assets/uploads/<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto" class="aluno-avatar">
                        <?php else: ?>
                            <div class="aluno-avatar-placeholder bg-gradient-to-br from-blue-400 to-purple-500">
                                <?= strtoupper(substr($aluno['nome'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate"><?= htmlspecialchars($aluno['nome']) ?></p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                        </svg>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Colunas de Cursos -->
    <?php foreach ($cursos as $curso): ?>
        <?php
        $alunosDoCurso = array_filter($alunos, function($aluno) use ($matriculasExistentes, $curso) {
            return isset($matriculasExistentes[$aluno['id']]) && $matriculasExistentes[$aluno['id']] == $curso['id'];
        });
        ?>
        <div class="kanban-column">
            <div class="kanban-header bg-gradient-to-r from-purple-500 to-pink-500 text-white">
                <div class="flex items-start gap-3">
                    <?php if ($curso['capa']): ?>
                        <img src="/assets/uploads/<?= htmlspecialchars($curso['capa']) ?>" alt="Capa" class="curso-capa">
                    <?php else: ?>
                        <div class="curso-capa-placeholder bg-white bg-opacity-20">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <a href="/diretor/curso_detalhes.php?id=<?= $curso['id'] ?>" class="hover:underline">
                            <h3 class="font-bold text-lg"><?= htmlspecialchars($curso['nome']) ?></h3>
                        </a>
                        <p class="text-sm opacity-90">Ano: <?= $curso['ano'] ?></p>
                        <div class="mt-2">
                            <span class="badge-count"><?= count($alunosDoCurso) ?> alunos</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="kanban-body" data-curso-id="<?= $curso['id'] ?>">
                <?php if (empty($alunosDoCurso)): ?>
                    <div class="empty-state">
                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <p class="text-sm">Arraste alunos para cá</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($alunosDoCurso as $aluno): ?>
                        <div class="aluno-card" draggable="true" data-aluno-id="<?= $aluno['id'] ?>">
                            <?php if ($aluno['foto']): ?>
                                <img src="/assets/uploads/<?= htmlspecialchars($aluno['foto']) ?>" alt="Foto" class="aluno-avatar">
                            <?php else: ?>
                                <div class="aluno-avatar-placeholder bg-gradient-to-br from-blue-400 to-purple-500">
                                    <?= strtoupper(substr($aluno['nome'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 truncate"><?= htmlspecialchars($aluno['nome']) ?></p>
                            </div>
                            <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                            </svg>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
let draggedElement = null;
let originalParent = null;

document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.aluno-card');
    const dropZones = document.querySelectorAll('.kanban-body');

    cards.forEach(card => {
        card.addEventListener('dragstart', handleDragStart);
        card.addEventListener('dragend', handleDragEnd);
    });

    dropZones.forEach(zone => {
        zone.addEventListener('dragover', handleDragOver);
        zone.addEventListener('drop', handleDrop);
        zone.addEventListener('dragleave', handleDragLeave);
    });
});

function handleDragStart(e) {
    draggedElement = this;
    originalParent = this.parentElement;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
}

function handleDragEnd(e) {
    this.classList.remove('dragging');
    document.querySelectorAll('.kanban-body').forEach(zone => {
        zone.classList.remove('drag-over');
    });
}

function handleDragOver(e) {
    if (e.preventDefault) {
        e.preventDefault();
    }
    e.dataTransfer.dropEffect = 'move';
    this.classList.add('drag-over');
    return false;
}

function handleDragLeave(e) {
    this.classList.remove('drag-over');
}

function handleDrop(e) {
    if (e.stopPropagation) {
        e.stopPropagation();
    }
    
    this.classList.remove('drag-over');
    
    if (draggedElement && this !== originalParent) {
        const alunoId = draggedElement.dataset.alunoId;
        const cursoId = this.dataset.cursoId;
        const cursoAnteriorId = originalParent.dataset.cursoId;
        
        this.appendChild(draggedElement);
        atualizarContadores();
        
        salvarMatricula(alunoId, cursoId, cursoAnteriorId);
    }
    
    return false;
}

function atualizarContadores() {
    document.querySelectorAll('.kanban-column').forEach(column => {
        const body = column.querySelector('.kanban-body');
        const badge = column.querySelector('.badge-count');
        const cards = body.querySelectorAll('.aluno-card');
        const count = cards.length;
        
        if (badge) {
            badge.textContent = count === 0 ? '0' : (count + (column.querySelector('.kanban-header h3').textContent.includes('Disponíveis') ? '' : ' alunos'));
        }
        
        const emptyState = body.querySelector('.empty-state');
        if (count === 0 && !emptyState) {
            const isDisponivel = column.querySelector('.kanban-header h3').textContent.includes('Disponíveis');
            body.innerHTML = `
                <div class="empty-state">
                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${isDisponivel ? 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' : 'M12 4v16m8-8H4'}"></path>
                    </svg>
                    <p class="text-sm">${isDisponivel ? 'Todos os alunos estão matriculados!' : 'Arraste alunos para cá'}</p>
                </div>
            `;
        } else if (count > 0 && emptyState) {
            emptyState.remove();
        }
    });
}

function salvarMatricula(alunoId, novoCursoId, cursoAnteriorId) {
    fetch('/diretor/api_matricula.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            aluno_id: alunoId,
            curso_id: novoCursoId,
            curso_anterior_id: cursoAnteriorId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarNotificacao(data.message, 'success');
        } else {
            mostrarNotificacao(data.message, 'error');
            location.reload();
        }
    })
    .catch(error => {
        mostrarNotificacao('Erro ao salvar matrícula', 'error');
        console.error('Erro:', error);
        location.reload();
    });
}

function mostrarNotificacao(mensagem, tipo) {
    const cor = tipo === 'success' ? 'bg-green-500' : 'bg-red-500';
    const icone = tipo === 'success' ? '✓' : '✗';
    
    const notificacao = document.createElement('div');
    notificacao.className = `fixed top-4 right-4 ${cor} text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2`;
    notificacao.innerHTML = `<span class="font-bold">${icone}</span> ${mensagem}`;
    
    document.body.appendChild(notificacao);
    
    setTimeout(() => {
        notificacao.remove();
    }, 3000);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
