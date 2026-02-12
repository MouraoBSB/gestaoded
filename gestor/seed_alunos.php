<?php
/**
 * Autor: Thiago Mourão
 * Instagram: https://www.instagram.com/mouraoeguerin/
 * Data: 2026-02-11 18:56:00
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireRole(['gestor']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = getConnection();
    
    $nomes = [
        'Ana Silva', 'João Santos', 'Maria Oliveira', 'Pedro Costa', 'Carla Souza',
        'Lucas Pereira', 'Juliana Lima', 'Rafael Alves', 'Fernanda Rocha', 'Bruno Martins',
        'Camila Ferreira', 'Diego Ribeiro', 'Patrícia Gomes', 'Rodrigo Cardoso', 'Aline Barbosa',
        'Marcelo Araújo', 'Vanessa Dias', 'Thiago Monteiro', 'Renata Castro', 'Felipe Nascimento',
        'Gabriela Freitas', 'André Moreira', 'Beatriz Carvalho', 'Gustavo Ramos', 'Larissa Correia',
        'Vinícius Teixeira', 'Natália Mendes', 'Leandro Pinto', 'Cristina Barros', 'Fábio Cavalcanti',
        'Priscila Azevedo', 'Henrique Duarte', 'Mônica Campos', 'Ricardo Nunes', 'Tatiana Rodrigues',
        'Eduardo Fernandes', 'Simone Batista', 'Maurício Lopes', 'Daniela Moura', 'Alexandre Reis',
        'Adriana Soares', 'Roberto Cunha', 'Luciana Pires', 'Sérgio Vieira', 'Márcia Fonseca',
        'Paulo Machado', 'Eliane Miranda', 'César Andrade', 'Rosana Melo', 'Wagner Torres'
    ];
    
    $enderecos = [
        'Rua das Flores, 123', 'Av. Principal, 456', 'Rua do Comércio, 789',
        'Travessa Central, 321', 'Alameda dos Anjos, 654', 'Rua São João, 987',
        'Av. Brasil, 147', 'Rua da Paz, 258', 'Rua Esperança, 369', 'Av. Paulista, 741'
    ];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO alunos (nome, endereco, data_nascimento, ativo) VALUES (?, ?, ?, 1)");
        
        $inseridos = 0;
        
        for ($i = 0; $i < 50; $i++) {
            $nome = $nomes[$i];
            $endereco = $enderecos[array_rand($enderecos)];
            
            $anoNascimento = rand(1960, 2010);
            $mesNascimento = str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT);
            $diaNascimento = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
            $dataNascimento = "$anoNascimento-$mesNascimento-$diaNascimento";
            
            $stmt->execute([$nome, $endereco, $dataNascimento]);
            $inseridos++;
        }
        
        $pdo->commit();
        
        setFlashMessage("$inseridos alunos fictícios criados com sucesso!", 'success');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlashMessage('Erro ao criar alunos: ' . $e->getMessage(), 'error');
    }
    
    redirect('/gestor/alunos.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="bg-yellow-50 border-2 border-yellow-400 rounded-lg p-6 mb-6">
        <div class="flex items-start gap-3">
            <svg class="w-6 h-6 text-yellow-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div>
                <h3 class="text-lg font-bold text-yellow-800 mb-2">⚠️ Função de Teste - Seed de Alunos</h3>
                <p class="text-yellow-700 mb-3">Esta função criará <strong>50 alunos fictícios</strong> no sistema para testes.</p>
                <p class="text-yellow-600 text-sm">Use apenas em ambiente de desenvolvimento/teste!</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-8">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Criar Alunos Fictícios</h2>
        
        <div class="mb-6">
            <h3 class="font-semibold text-gray-700 mb-2">O que será criado:</h3>
            <ul class="list-disc list-inside text-gray-600 space-y-1">
                <li>50 alunos com nomes brasileiros</li>
                <li>Endereços aleatórios</li>
                <li>Datas de nascimento entre 1960 e 2010</li>
                <li>Todos marcados como ativos</li>
            </ul>
        </div>
        
        <form method="POST" onsubmit="return confirm('Tem certeza que deseja criar 50 alunos fictícios?')">
            <div class="flex gap-3">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                    🌱 Criar 50 Alunos Fictícios
                </button>
                <a href="/gestor/alunos.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
    
    <div class="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="text-red-700 text-sm">
            <strong>Lembrete:</strong> Esta é uma função temporária para testes. 
            Remova este arquivo quando não for mais necessário.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
