<?php
/**
 * Autor: Thiago Mourao
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-14 15:17:00
 *
 * Pagina publica - Landing page de cursos disponiveis
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

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
<?php
    $seoConfigs = getSeoConfigs();
    $seoSiteTitle = $seoConfigs['seo_titulo_site'] ?? 'CEMA';
?>
    <title>Cursos Disponíveis - <?= htmlspecialchars($seoSiteTitle) ?></title>
<?= renderSeoMeta('Cursos Disponíveis', 'Explore nossos cursos de estudo espírita e inscreva-se gratuitamente.') ?>
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
                <div class="flex items-center gap-4">
                    <a href="/" class="hover:opacity-80 transition">
                        <img src="/assets/images/logo-cema.png" alt="Cursos CEMA" class="h-12">
                    </a>
                </div>
                <div class="flex-1 flex justify-center">
                    <a href="https://cemanet.org.br" target="_blank" class="hover:opacity-80 transition">
                        <img src="/assets/images/Logo Horizontal - CEMA.png" alt="CEMA" class="h-10">
                    </a>
                </div>
                <div>
                    <a href="/login.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition font-medium">
                        Área Restrita
                    </a>
                </div>
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
    <footer class="border-t border-gray-200 py-10" style="background: linear-gradient(135deg, #f3eddd 0%, #ffffff 100%);">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col items-center gap-6">
                <!-- Logo CEMA -->
                <a href="https://cemanet.org.br" target="_blank" class="hover:opacity-80 transition">
                    <img src="/assets/images/Logo Horizontal - CEMA.png" alt="CEMA" class="h-14">
                </a>

                <!-- Redes Sociais -->
                <div class="flex items-center gap-5">
                    <!-- WhatsApp -->
                    <a href="https://wa.me/5561995945976" target="_blank" class="text-gray-500 hover:text-green-500 transition" title="WhatsApp">
                        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </a>
                    <!-- Facebook -->
                    <a href="https://web.facebook.com/cemacentroespirita?locale=pt_BR" target="_blank" class="text-gray-500 hover:text-blue-600 transition" title="Facebook">
                        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>
                    <!-- Instagram -->
                    <a href="https://www.instagram.com/cemacentroespirita/" target="_blank" class="text-gray-500 hover:text-pink-500 transition" title="Instagram">
                        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                        </svg>
                    </a>
                    <!-- YouTube -->
                    <a href="https://www.youtube.com/c/CentroEsp%C3%ADritaMariaMadalena" target="_blank" class="text-gray-500 hover:text-red-600 transition" title="YouTube">
                        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                        </svg>
                    </a>
                    <!-- Radar CEMA -->
                    <a href="https://www.whatsapp.com/channel/0029VbAWpjCD8SDts4YtQa3E" target="_blank" class="hover:opacity-70 transition" title="Radar CEMA">
                        <img src="/assets/images/RADAR - Logo.png" alt="Radar CEMA" class="w-7 h-7 rounded-full object-cover">
                    </a>
                </div>

                <!-- Copyright -->
                <p class="text-sm text-gray-500">
                    &copy; <?= date('Y') ?> Centro Espírita Maria Madalena - Todos os direitos reservados
                </p>
            </div>
        </div>
    </footer>

</body>
</html>
