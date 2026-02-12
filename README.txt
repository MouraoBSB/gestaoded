================================================================================
SISTEMA DE CHAMADA DE CURSOS
================================================================================

Autor: Thiago Mourão
Instagram: https://www.instagram.com/mouraoeguerin/
Data: 2026-02-11 17:08:00

================================================================================
REQUISITOS DO SISTEMA
================================================================================

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor Apache com mod_rewrite habilitado
- Extensões PHP: PDO, PDO_MySQL, GD (para upload de imagens)

================================================================================
INSTALAÇÃO
================================================================================

1. Faça upload de todos os arquivos para o servidor web

2. Certifique-se de que a pasta 'assets/uploads/' tem permissão de escrita:
   chmod 755 assets/uploads/

3. Acesse o arquivo de instalação no navegador:
   http://seudominio.com/config/install.php

4. O sistema criará automaticamente todas as tabelas necessárias no banco de dados

5. Usuário padrão criado:
   Email: admin@sistema.com
   Senha: admin123
   
   IMPORTANTE: Altere a senha padrão após o primeiro login!

================================================================================
ESTRUTURA DO BANCO DE DADOS
================================================================================

Tabelas criadas:
- usuarios: Gestor, Diretor e Instrutor
- alunos: Cadastro de alunos
- cursos: Cursos oferecidos
- matriculas: Relação aluno-curso
- aulas: Registro de aulas
- presencas: Controle de presença/falta
- conclusoes: Aprovação/Reprovação com ano de conclusão

================================================================================
NÍVEIS DE ACESSO
================================================================================

GESTOR:
- Acesso total ao sistema
- Gerenciar usuários (criar, editar, desativar)
- Gerenciar alunos (cadastrar, editar, desativar)
- Gerenciar cursos (criar, editar, desativar)
- Visualizar relatórios completos

DIRETOR:
- Gerenciar matrículas (matricular alunos em cursos)
- Visualizar relatórios de frequência
- Acompanhar status de aprovação/reprovação

INSTRUTOR:
- Visualizar seus cursos atribuídos
- Registrar aulas
- Registrar presença/falta dos alunos
- Registrar aprovação/reprovação ao final do curso

================================================================================
FUNCIONALIDADES PRINCIPAIS
================================================================================

1. GESTÃO DE USUÁRIOS
   - Cadastro de Gestor, Diretor e Instrutor
   - Controle de acesso por tipo de usuário

2. GESTÃO DE ALUNOS
   - Cadastro com nome, foto, endereço e data de nascimento
   - Cálculo automático de idade
   - Upload de foto do aluno

3. GESTÃO DE CURSOS
   - Cadastro com nome e ano
   - Atribuição de instrutor ao curso

4. MATRÍCULAS
   - Diretor distribui alunos aos cursos
   - Controle de matrículas por curso

5. REGISTRO DE AULAS
   - Instrutor registra aulas com data e descrição
   - Controle de frequência por aula

6. PRESENÇA/FALTA
   - Registro individual de presença para cada aluno
   - Visualização de percentual de frequência

7. CONCLUSÃO DE CURSO
   - Instrutor registra aprovação/reprovação
   - Registro do ano de conclusão
   - Campo para observações

8. RELATÓRIOS
   - Relatórios por curso, ano e instrutor
   - Estatísticas de aprovação/reprovação
   - Controle de frequência dos alunos

================================================================================
SEGURANÇA
================================================================================

- Senhas criptografadas com password_hash()
- Proteção contra SQL Injection usando prepared statements
- Sanitização de dados de entrada
- Controle de sessão PHP
- Validação de permissões por nível de acesso
- Headers de segurança configurados no .htaccess

================================================================================
RESPONSIVIDADE
================================================================================

O sistema foi desenvolvido com abordagem mobile-first usando Tailwind CSS:
- Totalmente responsivo em desktop, tablet e mobile
- Interface adaptativa com breakpoints otimizados
- Navegação otimizada para dispositivos móveis

================================================================================
SUPORTE
================================================================================

Para dúvidas ou suporte, entre em contato:
Instagram: https://www.instagram.com/mouraoeguerin/

================================================================================
