# Autor: Thiago Mourão
# Instagram: https://www.instagram.com/mouraoeguerin/
# Data: 2026-02-11 17:22:00

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "CRIAÇÃO DE TABELAS - SISTEMA DE CHAMADA" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Verificar se o MySQL está instalado
$mysqlPath = Get-Command mysql -ErrorAction SilentlyContinue

if (-not $mysqlPath) {
    Write-Host "ERRO: MySQL não encontrado no PATH do sistema." -ForegroundColor Red
    Write-Host ""
    Write-Host "Por favor, execute o script SQL manualmente:" -ForegroundColor Yellow
    Write-Host "1. Acesse phpMyAdmin ou outro cliente MySQL" -ForegroundColor Yellow
    Write-Host "2. Selecione o banco 'cemaneto_chamadaded'" -ForegroundColor Yellow
    Write-Host "3. Execute o arquivo 'criar_tabelas.sql'" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Ou use o arquivo 'instalar.php' via navegador." -ForegroundColor Yellow
    exit 1
}

Write-Host "Executando script SQL no banco de dados remoto..." -ForegroundColor Green
Write-Host ""

$sqlFile = Join-Path $PSScriptRoot "criar_tabelas.sql"

if (-not (Test-Path $sqlFile)) {
    Write-Host "ERRO: Arquivo criar_tabelas.sql não encontrado!" -ForegroundColor Red
    exit 1
}

# Executar o script SQL
$sqlContent = Get-Content $sqlFile -Raw
$result = $sqlContent | & mysql -h 186.209.113.101 -u cemaneto_chamadaded -p'QIHIt}bZa}[chA@P' cemaneto_chamadaded 2>&1

if ($LASTEXITCODE -eq 0) {
    Write-Host "============================================" -ForegroundColor Green
    Write-Host "INSTALAÇÃO CONCLUÍDA COM SUCESSO!" -ForegroundColor Green
    Write-Host "============================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Tabelas criadas:" -ForegroundColor Cyan
    Write-Host "  - usuarios" -ForegroundColor White
    Write-Host "  - alunos" -ForegroundColor White
    Write-Host "  - cursos" -ForegroundColor White
    Write-Host "  - matriculas" -ForegroundColor White
    Write-Host "  - aulas" -ForegroundColor White
    Write-Host "  - presencas" -ForegroundColor White
    Write-Host "  - conclusoes" -ForegroundColor White
    Write-Host ""
    Write-Host "Credenciais de acesso:" -ForegroundColor Cyan
    Write-Host "  Email: admin@sistema.com" -ForegroundColor White
    Write-Host "  Senha: admin123" -ForegroundColor White
    Write-Host ""
    Write-Host "IMPORTANTE: Altere a senha após o primeiro login!" -ForegroundColor Yellow
} else {
    Write-Host "ERRO ao executar o script SQL:" -ForegroundColor Red
    Write-Host $result -ForegroundColor Red
    Write-Host ""
    Write-Host "Tente executar manualmente via phpMyAdmin." -ForegroundColor Yellow
}
