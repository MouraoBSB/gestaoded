# 🚀 Como Iniciar o Sistema de Gestão de Cursos CEMA

## 📋 Pré-requisitos

Antes de iniciar o sistema, certifique-se de ter:

- ✅ PHP 8.0 ou superior instalado
- ✅ MySQL/MariaDB instalado e rodando
- ✅ Servidor web (Apache, Nginx ou PHP built-in server)
- ✅ Extensões PHP necessárias: PDO, PDO_MySQL, GD

---

## 🔧 Configuração Inicial (Primeira vez)

### 1. Configurar Banco de Dados

Edite o arquivo `config/database.php` com suas credenciais:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'chamada_ded');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
```

### 2. Criar Banco de Dados

Execute no MySQL:

```sql
CREATE DATABASE chamada_ded CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Instalar Tabelas

Acesse no navegador:
```
http://localhost:8000/instalar.php
```

### 4. Executar Atualizações

Acesse no navegador:
```
http://localhost:8000/atualizar_banco.php
```

Isso criará:
- ✅ Tabela `configuracoes` (para SMTP)
- ✅ Tabela `tokens_recuperacao` (para recuperação de senha)
- ✅ Tabela `curso_instrutores` (para múltiplos instrutores por curso)
- ✅ Coluna `foto` em usuários
- ✅ Coluna `capa` em cursos

### 5. Criar Usuário Gestor Inicial

Acesse:
```
http://localhost:8000/criar_gestor.php
```

Ou execute no MySQL:

```sql
INSERT INTO usuarios (nome, email, senha, tipo) 
VALUES ('Administrador', 'admin@cema.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gestor');
```
**Senha padrão:** `password`

---

## ▶️ Iniciar o Sistema

### Opção 1: Servidor PHP Built-in (Desenvolvimento)

Abra o terminal na pasta do projeto e execute:

```bash
php -S localhost:8000
```

Acesse: `http://localhost:8000`

### Opção 2: XAMPP/WAMP

1. Copie a pasta do projeto para `htdocs` (XAMPP) ou `www` (WAMP)
2. Inicie Apache e MySQL
3. Acesse: `http://localhost/Chamada%20DED`

### Opção 3: Laragon

1. Copie a pasta do projeto para `laragon/www`
2. Inicie Laragon
3. Acesse: `http://chamada-ded.test`

---

## 🔐 Primeiro Acesso

1. Acesse a URL do sistema
2. Faça login com:
   - **Email:** admin@cema.com
   - **Senha:** password
3. **IMPORTANTE:** Altere a senha imediatamente após o primeiro login

---

## ⚙️ Configurações Pós-Instalação

### 1. Configurar SMTP (Recuperação de Senha)

1. Faça login como gestor
2. Clique no seu nome → **Configurações SMTP**
3. Preencha os dados do servidor de email:
   - Host SMTP
   - Porta (587 para TLS, 465 para SSL)
   - Usuário
   - Senha
   - Email remetente
   - Nome remetente
4. Teste o envio de email

### 2. Personalizar Template de Email

1. Clique no seu nome → **Template de Email**
2. Edite o HTML do template
3. Use os placeholders:
   - `{{TITULO}}` - Título do email
   - `{{MENSAGEM}}` - Conteúdo do email
   - `{{LINK}}` - Link de ação (quando aplicável)

### 3. Criar Instrutores

1. Vá em **Gerenciar Instrutores**
2. Clique em **+ Novo Instrutor**
3. Preencha os dados e faça upload da foto
4. Salve

### 4. Criar Cursos

1. Vá em **Gerenciar Cursos**
2. Clique em **+ Novo Curso**
3. Preencha os dados
4. Selecione de 1 a 4 instrutores
5. Faça upload da capa (1080x1350px)
6. Salve

### 5. Cadastrar Alunos

1. Vá em **Gerenciar Alunos**
2. Clique em **+ Novo Aluno**
3. Preencha os dados
4. Faça upload da foto
5. Salve

### 6. Vincular Alunos a Cursos

1. Vá em **Vincular Alunos a Cursos** (Kanban)
2. Arraste e solte alunos nos cursos desejados

---

## 📁 Estrutura de Pastas Importantes

```
Chamada DED/
├── assets/
│   ├── images/          # Logo CEMA e imagens do sistema
│   └── uploads/         # Fotos de usuários, alunos e capas de cursos
├── config/
│   └── database.php     # Configurações do banco de dados
├── includes/
│   ├── auth.php         # Autenticação
│   ├── functions.php    # Funções auxiliares
│   ├── email.php        # Envio de emails
│   ├── header.php       # Cabeçalho do sistema
│   └── footer.php       # Rodapé do sistema
├── gestor/              # Páginas do gestor
├── diretor/             # Páginas do diretor
├── instrutor/           # Páginas do instrutor
└── index.php            # Página inicial
```

---

## 🎨 Paleta de Cores CEMA

O sistema utiliza as seguintes cores:

- **Roxo:** #4e4483 (Cor principal)
- **Azul:** #6e9fcb (Complementar)
- **Verde água:** #89ab98 (Harmonia)
- **Laranja:** #e79048 (Destaque)
- **Bege:** #f3eddd (Fundo)

---

## 🔄 Atualizações Futuras

Quando houver atualizações no sistema:

1. Faça backup do banco de dados
2. Substitua os arquivos do sistema
3. Acesse: `http://localhost:8000/atualizar_banco.php`
4. Verifique se tudo está funcionando

---

## 🆘 Solução de Problemas

### Erro de Conexão com Banco de Dados
- Verifique as credenciais em `config/database.php`
- Certifique-se de que o MySQL está rodando
- Verifique se o banco de dados foi criado

### Erro de Upload de Imagens
- Verifique permissões da pasta `assets/uploads/`
- No Linux/Mac: `chmod 755 assets/uploads/`
- Certifique-se de que a extensão GD do PHP está ativada

### Erro ao Enviar Emails
- Verifique as configurações SMTP
- Teste com o botão "Enviar Email de Teste"
- Verifique se a porta está correta (587 ou 465)

### Página em Branco
- Ative a exibição de erros no PHP
- Verifique os logs de erro do servidor
- Certifique-se de que todas as extensões PHP estão instaladas

---

## 📞 Suporte

Sistema desenvolvido por **DECOM - Departamento de Comunicação e Multimídia**

Para suporte técnico, entre em contato com a equipe de desenvolvimento.

---

## 🔒 Segurança

**IMPORTANTE:**
- ❌ Nunca compartilhe as credenciais do banco de dados
- ❌ Não use senhas fracas
- ✅ Altere a senha padrão do gestor
- ✅ Faça backups regulares do banco de dados
- ✅ Mantenha o PHP e MySQL atualizados
- ✅ Use HTTPS em produção

---

**Sistema de Gestão de Cursos CEMA - Versão 1.0**
