<#
    Script Automático de Migration - Portal Cursos CEMA
    Autor: Thiago Mourão - https://www.instagram.com/mouraoeguerin/
    Data: 2026-03-18
    Versão: 1.0.0
#>

[CmdletBinding()]
Param(
    [Parameter(Mandatory=$false)]
    [string]$Novo = "",
    
    [Parameter(Mandatory=$false)]
    [switch]$Rodar
)

# Configurações do Sistema
$DiretorioProjeto = Split-Path -Parent $MyInvocation.MyCommand.Path
$BaseUrl = "https://portal.cursoscema.com.br"
$ApiUrl = "$BaseUrl/api_migrations.php"
$TokenSeguranca = "CHAMADA_DED_SECURE_MIGRATE_2026"

$PastaMigrations = Join-Path $DiretorioProjeto "database\migrations"
$ScriptDeploy = Join-Path $DiretorioProjeto "deploy.ps1"

function Escrever-Log {
    param ([string]$Mensagem, [string]$Tipo = "INFO")
    $cor = switch ($Tipo) {
        "ERRO" { "Red" }
        "OK"   { "Green" }
        "AVISO"{ "Yellow" }
        default { "Cyan" }
    }
    $hora = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
    Write-Host "[$hora] [$Tipo] $Mensagem" -ForegroundColor $cor
}

# ============================================================
# Criar pasta migrations caso não exista localmente
# ============================================================
if (-not (Test-Path $PastaMigrations)) {
    New-Item -ItemType Directory -Path $PastaMigrations -Force | Out-Null
    Escrever-Log "Pasta criada com sucesso: $PastaMigrations" "OK"
}

# ============================================================
# 1) CRIAR NOVO ARQUIVO DE MIGRATION
# ============================================================
if (-not [string]::IsNullOrWhiteSpace($Novo)) {
    $timestamp = (Get-Date).ToString("yyyy_MM_dd_HHmmss")
    $nomeSanitizado = $Novo -replace '[^a-zA-Z0-9]', '_'
    $nomeArquivo = "${timestamp}_${nomeSanitizado}.sql"
    $caminhoCompleto = Join-Path $PastaMigrations $nomeArquivo
    
    $cabecalho = "-- Migration: $Novo`n-- Data: $((Get-Date).ToString('dd/MM/yyyy HH:mm:ss'))`n-- Autor: Thiago Mourão`n`n-- ESCREVA SEU COMANDO SQL AQUI EMBAIXO:`n`n"
    
    Set-Content -Path $caminhoCompleto -Value $cabecalho -Encoding UTF8
    
    Write-Host ""
    Escrever-Log " ARQUIVO DE MIGRATION CRIADO ".PadRight(50, "=").PadLeft(60, "=") "OK"
    Escrever-Log "Arquivo: database\migrations\$nomeArquivo" "OK"
    Escrever-Log "Abra o arquivo no seu editor, coloque o código SQL e salve." "INFO"
    Write-Host ""
    exit
}

# ============================================================
# 2) RODAR AS MIGRATIONS NA PRODUÇÃO (NapoleonHost)
# ============================================================
if ($Rodar) {
    Write-Host ""
    Escrever-Log " ETAPA 1: SINCRONIZANDO ARQUIVOS (Auto Deploy) ".PadRight(60,"=") "INFO"
    
    if (Test-Path $ScriptDeploy) {
        # O deploy no modo Automático manda arquivos modificados nas últimas 24h sem perguntar
        & $ScriptDeploy -AutoHoras 24
    } else {
        Escrever-Log "Script deploy.ps1 não encontrado na raiz! Sincronização cancelada." "ERRO"
        exit
    }
    
    Escrever-Log " ETAPA 2: EXECUTANDO NO BANCO DA PRODUÇÃO ".PadRight(60,"=") "INFO"
    Escrever-Log "Dando gatilho seguro na API: $ApiUrl" "AVISO"
    
    try {
        $headers = @{ "X-Migration-Token" = $TokenSeguranca }
        $resposta = Invoke-RestMethod -Uri $ApiUrl -Headers $headers -Method Post -TimeoutSec 60
        
        if ($resposta.sucesso) {
            Escrever-Log "Resultado: $($resposta.mensagem)" "OK"
            
            if ($null -ne $resposta.arquivos_rodados -and $resposta.arquivos_rodados.Count -gt 0) {
                foreach ($arq in $resposta.arquivos_rodados) {
                    Write-Host "    [OK] $arq" -ForegroundColor Green
                }
            }
            Write-Host ""
        } else {
            Escrever-Log "A API de banco retornou um Erro Interno:" "ERRO"
            Escrever-Log $resposta.erro "ERRO"
        }
    } catch {
        Escrever-Log "A requisição falhou: $($_.Exception.Message)" "ERRO"
        
        # Tentativa de extrair o body da resposta HTTP para ler o log do PHP
        if ($_.Exception.Response) {
            $stream = $_.Exception.Response.GetResponseStream()
            $reader = New-Object System.IO.StreamReader($stream)
            $respBody = $reader.ReadToEnd()
            Escrever-Log "Detalhes do Servidor: $respBody" "ERRO"
        }
    }
    
    exit
}

# ============================================================
# TELA DE AJUDA
# ============================================================
Clear-Host
Write-Host ""
Write-Host "  ======================================================" -ForegroundColor Cyan
Write-Host "                                                        " -ForegroundColor Cyan
Write-Host "          SISTEMA DE MIGRATIONS - Chamada DED           " -ForegroundColor Cyan
Write-Host "          (Banco de Producao Remoto Integrado)          " -ForegroundColor Cyan
Write-Host "                                                        " -ForegroundColor Cyan
Write-Host "  ======================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "  Autor: Thiago Mourao" -ForegroundColor DarkGray
Write-Host "  Versao: 1.0.0" -ForegroundColor DarkGray
Write-Host ""
Write-Host "  COMO USAR PELA LINHA DE COMANDO:" -ForegroundColor Yellow
Write-Host ""
Write-Host "  1) PARA GERAR UM NOVO ARQUIVO SQL EM BRANCO:" -ForegroundColor White
Write-Host "     .\migrate.ps1 -Novo `"nome_da_minha_tabela`"" -ForegroundColor Green
Write-Host ""
Write-Host "  2) PARA SUBIR OS ARQUIVOS E EXECUTAR TUDO NA PRODUCAO:" -ForegroundColor White
Write-Host "     .\migrate.ps1 -Rodar" -ForegroundColor Green
Write-Host ""
Write-Host "  ======================================================" -ForegroundColor Cyan
Write-Host ""
