# 📝 Como Fazer Commit do Sistema de Gestão de Cursos CEMA

## 🔧 Configuração Inicial do Git (Primeira vez)

### 1. Inicializar Repositório Git

Abra o terminal na pasta do projeto e execute:

```bash
git init
```

### 2. Configurar Informações do Usuário

```bash
git config user.name "Seu Nome"
git config user.email "seu.email@exemplo.com"
```

### 3. Criar Arquivo .gitignore

Crie o arquivo `.gitignore` na raiz do projeto com o seguinte conteúdo:

```
# Arquivos de configuração sensíveis
config/database.php

# Uploads de usuários
assets/uploads/*
!assets/uploads/.gitkeep

# Logs
*.log

# Cache do sistema
cache/
temp/

# Arquivos do sistema operacional
.DS_Store
Thumbs.db
desktop.ini

# IDEs
.vscode/
.idea/
*.swp
*.swo
*~

# Dependências (se usar Composer futuramente)
vendor/
composer.lock

# Arquivos temporários
*.tmp
*.bak
*.old
```

### 4. Criar Arquivo .gitkeep para Pastas Vazias

```bash
touch assets/uploads/.gitkeep
```

---

## 📦 Fazer o Primeiro Commit

### 1. Adicionar Todos os Arquivos

```bash
git add .
```

### 2. Verificar o que Será Commitado

```bash
git status
```

### 3. Fazer o Commit Inicial

```bash
git commit -m "🎉 Commit inicial: Sistema de Gestão de Cursos CEMA

- Sistema completo de gestão de cursos
- Paleta de cores CEMA implementada
- Múltiplos instrutores por curso (1-4)
- Sistema de recuperação de senha
- Upload de fotos e capas
- Dashboard responsivo
- Desenvolvido por DECOM"
```

---

## 🔄 Commits Futuros

### Estrutura de Mensagem de Commit

Use emojis e seja descritivo:

```bash
git commit -m "emoji Título curto (máx 50 caracteres)

- Descrição detalhada do que foi alterado
- Pode ter múltiplas linhas
- Seja específico e claro"
```

### Emojis Recomendados

- ✨ `:sparkles:` - Nova funcionalidade
- 🐛 `:bug:` - Correção de bug
- 🎨 `:art:` - Melhorias de UI/UX
- ♻️ `:recycle:` - Refatoração de código
- 📝 `:memo:` - Documentação
- 🔒 `:lock:` - Segurança
- ⚡ `:zap:` - Performance
- 🚀 `:rocket:` - Deploy/Release
- 🔧 `:wrench:` - Configuração
- 🗃️ `:card_file_box:` - Banco de dados

### Exemplos de Commits

**Nova funcionalidade:**
```bash
git add gestor/relatorios.php
git commit -m "✨ Adicionar página de relatórios

- Relatório de frequência por curso
- Relatório de alunos aprovados/reprovados
- Exportação para PDF"
```

**Correção de bug:**
```bash
git add gestor/cursos.php
git commit -m "🐛 Corrigir erro ao editar curso sem instrutor

- Validação de instrutores vazios
- Mensagem de erro mais clara"
```

**Melhoria visual:**
```bash
git add includes/header.php login.php
git commit -m "🎨 Aplicar paleta de cores CEMA

- Gradiente roxo-azul no header
- Logo CEMA adicionada
- Mensagens flash com cores da paleta"
```

---

## 🌐 Enviar para Repositório Remoto (GitHub/GitLab)

### 1. Criar Repositório no GitHub

1. Acesse https://github.com
2. Clique em "New repository"
3. Nome: `sistema-gestao-cursos-cema`
4. Descrição: "Sistema de Gestão de Cursos - CEMA"
5. **NÃO** marque "Initialize with README"
6. Clique em "Create repository"

### 2. Adicionar Repositório Remoto

```bash
git remote add origin https://github.com/seu-usuario/sistema-gestao-cursos-cema.git
```

### 3. Enviar Commits para o GitHub

```bash
git branch -M main
git push -u origin main
```

### 4. Commits Futuros

Após o primeiro push, use apenas:

```bash
git push
```

---

## 🔀 Workflow Recomendado

### Fluxo de Trabalho Diário

1. **Antes de começar a trabalhar:**
   ```bash
   git pull
   ```

2. **Fazer alterações no código**

3. **Verificar o que mudou:**
   ```bash
   git status
   git diff
   ```

4. **Adicionar arquivos específicos:**
   ```bash
   git add arquivo1.php arquivo2.php
   ```
   
   Ou adicionar tudo:
   ```bash
   git add .
   ```

5. **Fazer commit:**
   ```bash
   git commit -m "emoji Descrição da alteração"
   ```

6. **Enviar para o repositório:**
   ```bash
   git push
   ```

---

## 🌿 Trabalhando com Branches

### Criar Branch para Nova Funcionalidade

```bash
git checkout -b feature/nome-da-funcionalidade
```

### Fazer Commits na Branch

```bash
git add .
git commit -m "✨ Implementar funcionalidade X"
git push -u origin feature/nome-da-funcionalidade
```

### Voltar para Branch Principal

```bash
git checkout main
```

### Mesclar Branch

```bash
git checkout main
git merge feature/nome-da-funcionalidade
git push
```

### Deletar Branch Após Mesclar

```bash
git branch -d feature/nome-da-funcionalidade
git push origin --delete feature/nome-da-funcionalidade
```

---

## 📋 Comandos Úteis

### Ver Histórico de Commits

```bash
git log
git log --oneline
git log --graph --oneline --all
```

### Desfazer Alterações Não Commitadas

```bash
git checkout -- arquivo.php
```

### Desfazer Último Commit (mantendo alterações)

```bash
git reset --soft HEAD~1
```

### Ver Diferenças

```bash
git diff                    # Alterações não staged
git diff --staged          # Alterações staged
git diff HEAD              # Todas as alterações
```

### Atualizar do Repositório Remoto

```bash
git pull
```

### Ver Branches

```bash
git branch              # Locais
git branch -r           # Remotas
git branch -a           # Todas
```

---

## 🔐 Arquivo de Configuração Exemplo

Crie `config/database.example.php` para versionar:

```php
<?php
// Copie este arquivo para database.php e configure suas credenciais

define('DB_HOST', 'localhost');
define('DB_NAME', 'chamada_ded');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
define('DB_CHARSET', 'utf8mb4');
```

Adicione ao `.gitignore`:
```
config/database.php
```

Versione apenas:
```
config/database.example.php
```

---

## ⚠️ Boas Práticas

### ✅ FAÇA:
- Commits pequenos e frequentes
- Mensagens descritivas
- Use .gitignore para arquivos sensíveis
- Teste antes de fazer commit
- Pull antes de push
- Use branches para funcionalidades grandes

### ❌ NÃO FAÇA:
- Commitar senhas ou credenciais
- Commits gigantes com muitas alterações
- Mensagens vagas ("fix", "update", "changes")
- Commitar arquivos de configuração local
- Commitar uploads de usuários
- Push direto em produção sem testar

---

## 🚨 Segurança

**NUNCA commite:**
- ❌ Senhas do banco de dados
- ❌ Chaves de API
- ❌ Tokens de acesso
- ❌ Arquivos de configuração com dados sensíveis
- ❌ Uploads de usuários (fotos, documentos)

**SEMPRE use:**
- ✅ Arquivos `.example` para configurações
- ✅ Variáveis de ambiente
- ✅ `.gitignore` adequado
- ✅ Documentação de como configurar

---

## 📞 Suporte

Para dúvidas sobre Git:
- Documentação oficial: https://git-scm.com/doc
- GitHub Guides: https://guides.github.com

**Sistema desenvolvido por DECOM - Departamento de Comunicação e Multimídia**
