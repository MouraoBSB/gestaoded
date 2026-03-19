# Sistema de Turmas - Guia Completo

**Autor:** Thiago Mourão  
**Instagram:** https://www.instagram.com/mouraoeguerin/  
**Data:** 2026-02-14

---

## 📋 Visão Geral

O sistema foi reestruturado para separar **Cursos Base** de **Turmas**. Isso permite que um mesmo curso (ex: "Noções Básicas") seja oferecido em múltiplos anos e semestres sem duplicação de dados.

---

## 🎯 Conceitos Principais

### **Curso Base**
- Informações gerais do curso
- Nome (ex: "Noções Básicas", "Nosso Lar")
- Descrição
- Carga horária
- Tipo de período (Anual ou Semestral)
- Capa
- Criado **uma única vez**

### **Turma**
- Oferta específica de um curso
- Vinculada a um curso base
- Ano (ex: 2026)
- Semestre (1º ou 2º - apenas para cursos semestrais)
- Datas de início e fim
- Vagas
- Instrutores (1 a 4 por turma)
- Status: **Ativa** ou **Fechada**
- Inscrições: **Abertas** ou **Fechadas**

---

## 🔄 Fluxo de Trabalho

### **1. Executar Migração (Apenas uma vez)**

Acesse: `http://localhost:8000/migrar_para_turmas.php`

**O que será feito:**
- ✅ Criação das tabelas `turmas` e `turma_instrutores`
- ✅ Adição do campo `tipo_periodo` na tabela `cursos`
- ✅ Migração dos cursos existentes para turmas
- ✅ Atualização de relacionamentos (matrículas, aulas, conclusões)

**Importante:**
- Faça backup do banco antes de executar
- A migração é irreversível
- Todos os dados serão preservados

---

### **2. Gerenciar Cursos Base**

**Acesso:** Dashboard → Gerenciar Cursos

**Criar Novo Curso:**
1. Clique em "+ Novo Curso"
2. Preencha:
   - Nome do Curso
   - Descrição (opcional)
   - Carga Horária
   - Tipo de Período (Anual ou Semestral)
   - Capa (opcional)
3. Clique em "Criar Curso"

**Editar Curso:**
- Clique em "Editar" na linha do curso
- Modifique os dados necessários
- Salve as alterações

**Visualizar Turmas:**
- Clique no número de turmas na coluna "Turmas"
- Será redirecionado para a página de turmas filtrada por aquele curso

---

### **3. Gerenciar Turmas**

**Acesso:** Dashboard → Gerenciar Turmas

#### **Criar Nova Turma:**

1. Clique em "+ Nova Turma"
2. Preencha:
   - **Curso:** Selecione o curso base
   - **Ano:** Ano da oferta (ex: 2026)
   - **Semestre:** Aparece apenas se o curso for semestral
   - **Data Início/Fim:** Opcional
   - **Vagas:** Número de vagas disponíveis
   - **Instrutores:** Selecione de 1 a 4 instrutores
3. Clique em "Criar Turma"

#### **Controles de Turma:**

**Switch 1: Status da Turma**
- 🟢 **Ativa:** Turma em andamento, aulas acontecendo
- 🔴 **Fechada:** Turma concluída, conclusões registradas
- Ao fechar: bloqueia novas matrículas e edições de presença
- Permite editar conclusões mesmo após fechamento

**Switch 2: Inscrições**
- 🟢 **Abertas:** Aceita novas matrículas, aparece na página pública
- 🔴 **Fechadas:** Não aceita mais inscrições

#### **Editar Turma:**
- Clique em "Editar"
- Modifique ano, semestre, datas, vagas ou instrutores
- Salve as alterações

---

## 📊 Cenários de Uso

### **Curso Semestral:**

**Exemplo: Noções Básicas**

```
Curso Base: Noções Básicas (Semestral, 40h)

Turmas:
├─ 2025/1º Semestre (Fechada, Inscrições Fechadas) - 25 alunos
├─ 2025/2º Semestre (Fechada, Inscrições Fechadas) - 30 alunos
├─ 2026/1º Semestre (Ativa, Inscrições Fechadas) - 28 alunos
└─ 2026/2º Semestre (Ativa, Inscrições Abertas) - 0 alunos
```

### **Curso Anual:**

**Exemplo: Evangelho Rede Vivo**

```
Curso Base: Evangelho Rede Vivo (Anual, 60h)

Turmas:
├─ 2024 (Fechada, Inscrições Fechadas) - 35 alunos
├─ 2025 (Fechada, Inscrições Fechadas) - 40 alunos
└─ 2026 (Ativa, Inscrições Abertas) - 15 alunos
```

---

## 🎓 Processo Completo de um Curso

### **Fase 1: Planejamento**
1. Criar curso base (se não existir)
2. Criar turma para o período desejado
3. Definir instrutores
4. Abrir inscrições

### **Fase 2: Inscrições**
- Turma aparece na página pública
- Alunos se inscrevem
- Gestor pode matricular alunos manualmente
- Fechar inscrições quando atingir limite ou iniciar aulas

### **Fase 3: Execução**
- Instrutor registra aulas
- Instrutor registra presenças
- Turma permanece "Ativa"

### **Fase 4: Conclusão**
- Instrutor registra conclusões (aprovado/reprovado)
- Gestor/Instrutor clica no switch para "Fechar Turma"
- Turma fica com status "Fechada"
- Histórico preservado

---

## 🔍 Filtros e Buscas

### **Página de Turmas:**
- Filtrar por curso
- Filtrar por status (Ativa/Fechada)
- Ver total de alunos matriculados
- Ver vagas disponíveis

### **Página de Relatórios:**
- Buscar aluno por nome (AJAX)
- Ver histórico completo do aluno
- Ver todas as turmas que o aluno participou
- Filtrar por curso ou ano

---

## 📱 Página Pública (Futuro)

A página pública mostrará apenas turmas que atendem:
- ✅ Curso ativo
- ✅ Turma ativa
- ✅ Inscrições abertas

**Exemplo de exibição:**

```
Cursos Disponíveis para Inscrição

📚 Noções Básicas - 2026/2º Semestre
   Início: 01/07/2026 | Vagas: 30 | Carga: 40h
   Instrutores: João Silva, Maria Santos
   [Inscrever-se]

📖 Nosso Lar - 2026/2º Semestre  
   Início: 15/07/2026 | Vagas: 25 | Carga: 50h
   Instrutores: Pedro Costa
   [Inscrever-se]
```

---

## ⚠️ Observações Importantes

### **Diferenças do Sistema Anterior:**

**Antes:**
- Curso = Nome + Ano
- Duplicação de cursos por ano/semestre
- Instrutores vinculados ao curso

**Agora:**
- Curso = Informações base (criado uma vez)
- Turma = Oferta específica (ano/semestre)
- Instrutores vinculados à turma

### **Compatibilidade:**

- ✅ Todos os dados existentes foram migrados
- ✅ Matrículas vinculadas às turmas
- ✅ Aulas vinculadas às turmas
- ✅ Conclusões vinculadas às turmas
- ✅ Histórico completo preservado

### **Benefícios:**

- ✅ Sem duplicação de dados
- ✅ Facilita relatórios gerais por curso
- ✅ Controle granular de inscrições
- ✅ Gestão de status de turmas
- ✅ Preparado para página pública
- ✅ Escalável para múltiplos períodos

---

## 🆘 Solução de Problemas

### **Turma não aparece na lista:**
- Verifique se o curso está ativo
- Verifique os filtros aplicados

### **Não consigo fechar turma:**
- Verifique se todas as conclusões foram registradas
- O switch pode ser ativado a qualquer momento

### **Erro ao criar turma:**
- Verifique se o curso base existe
- Verifique se já não existe turma para o mesmo período
- Verifique se selecionou pelo menos 1 instrutor

### **Aluno não aparece no histórico:**
- Execute o script de migração se ainda não executou
- Verifique se o aluno tem matrículas ativas

---

## 📞 Suporte

Para dúvidas ou problemas:
- **Desenvolvedor:** Thiago Mourão
- **Instagram:** @mouraoeguerin
- **Email:** tobrasil@gmail.com

---

**Sistema de Gestão de Cursos CEMA**  
**Versão 2.0 - Sistema de Turmas**  
**© 2026 - Todos os direitos reservados**
