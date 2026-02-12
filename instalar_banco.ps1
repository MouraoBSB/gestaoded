# Autor: Thiago Mourão
# Instagram: https://www.instagram.com/mouraoeguerin/
# Data: 2026-02-11 17:27:00

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "INSTALAÇÃO DO SISTEMA DE CHAMADA" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Configurações do banco de dados
$dbHost = "186.209.113.101"
$dbUser = "cemaneto_chamadaded"
$dbPass = "QIHIt}bZa}[chA@P"
$dbName = "cemaneto_chamadaded"

Write-Host "Conectando ao banco de dados remoto..." -ForegroundColor Yellow
Write-Host "Host: $dbHost" -ForegroundColor Gray
Write-Host "Database: $dbName" -ForegroundColor Gray
Write-Host ""

# Carregar assembly do MySQL (se disponível)
try {
    Add-Type -Path "MySql.Data.dll" -ErrorAction Stop
} catch {
    # Tentar usar .NET para conexão direta
}

# Função para executar SQL usando .NET
function Execute-MySQLQuery {
    param(
        [string]$Query
    )
    
    try {
        # Criar string de conexão
        $connectionString = "Server=$dbHost;Database=$dbName;Uid=$dbUser;Pwd=$dbPass;CharSet=utf8mb4;"
        
        # Tentar carregar MySql.Data
        $assemblyPath = Join-Path $env:USERPROFILE ".nuget\packages\mysql.data\*\lib\net*\MySql.Data.dll"
        $assembly = Get-ChildItem $assemblyPath -ErrorAction SilentlyContinue | Select-Object -First 1
        
        if ($assembly) {
            Add-Type -Path $assembly.FullName
            $connection = New-Object MySql.Data.MySqlClient.MySqlConnection($connectionString)
            $connection.Open()
            
            $command = $connection.CreateCommand()
            $command.CommandText = $Query
            $command.ExecuteNonQuery() | Out-Null
            
            $connection.Close()
            return $true
        } else {
            return $false
        }
    } catch {
        Write-Host "Erro: $_" -ForegroundColor Red
        return $false
    }
}

# Ler arquivo SQL
$sqlFile = Join-Path $PSScriptRoot "criar_tabelas.sql"

if (-not (Test-Path $sqlFile)) {
    Write-Host "ERRO: Arquivo criar_tabelas.sql não encontrado!" -ForegroundColor Red
    exit 1
}

Write-Host "Lendo arquivo SQL..." -ForegroundColor Yellow
$sqlContent = Get-Content $sqlFile -Raw

# Dividir em comandos individuais
$commands = $sqlContent -split ';' | Where-Object { $_.Trim() -ne '' -and -not $_.Trim().StartsWith('--') }

Write-Host "Encontrados $($commands.Count) comandos SQL" -ForegroundColor Green
Write-Host ""

# Tentar executar via MySQL .NET
$success = $false

foreach ($cmd in $commands) {
    $cmd = $cmd.Trim()
    if ($cmd) {
        $result = Execute-MySQLQuery -Query $cmd
        if ($result) {
            $success = $true
            if ($cmd -match 'CREATE TABLE.*?(\w+)') {
                Write-Host "✓ Tabela processada" -ForegroundColor Green
            }
        }
    }
}

if (-not $success) {
    Write-Host ""
    Write-Host "============================================" -ForegroundColor Yellow
    Write-Host "INSTALAÇÃO MANUAL NECESSÁRIA" -ForegroundColor Yellow
    Write-Host "============================================" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "O MySQL .NET não está disponível neste sistema." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Por favor, use uma das seguintes opções:" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "OPÇÃO 1: Via Navegador (Mais Fácil)" -ForegroundColor Green
    Write-Host "  1. Faça upload dos arquivos para seu servidor" -ForegroundColor White
    Write-Host "  2. Acesse: http://seudominio.com/instalar.php" -ForegroundColor White
    Write-Host ""
    Write-Host "OPÇÃO 2: Via phpMyAdmin" -ForegroundColor Green
    Write-Host "  1. Acesse phpMyAdmin do seu servidor" -ForegroundColor White
    Write-Host "  2. Selecione o banco 'cemaneto_chamadaded'" -ForegroundColor White
    Write-Host "  3. Clique na aba SQL" -ForegroundColor White
    Write-Host "  4. Copie o conteúdo de 'criar_tabelas.sql'" -ForegroundColor White
    Write-Host "  5. Cole e execute" -ForegroundColor White
    Write-Host ""
    Write-Host "OPÇÃO 3: Via cPanel" -ForegroundColor Green
    Write-Host "  1. Acesse cPanel do seu hosting" -ForegroundColor White
    Write-Host "  2. Vá em Banco de Dados MySQL > phpMyAdmin" -ForegroundColor White
    Write-Host "  3. Execute o arquivo criar_tabelas.sql" -ForegroundColor White
    Write-Host ""
} else {
    Write-Host ""
    Write-Host "============================================" -ForegroundColor Green
    Write-Host "INSTALAÇÃO CONCLUÍDA COM SUCESSO!" -ForegroundColor Green
    Write-Host "============================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Tabelas criadas:" -ForegroundColor Cyan
    Write-Host "  • usuarios" -ForegroundColor White
    Write-Host "  • alunos" -ForegroundColor White
    Write-Host "  • cursos" -ForegroundColor White
    Write-Host "  • matriculas" -ForegroundColor White
    Write-Host "  • aulas" -ForegroundColor White
    Write-Host "  • presencas" -ForegroundColor White
    Write-Host "  • conclusoes" -ForegroundColor White
    Write-Host ""
    Write-Host "Credenciais de acesso:" -ForegroundColor Cyan
    Write-Host "  Email: admin@sistema.com" -ForegroundColor White
    Write-Host "  Senha: admin123" -ForegroundColor White
    Write-Host ""
    Write-Host "⚠ IMPORTANTE: Altere a senha após o primeiro login!" -ForegroundColor Yellow
}
