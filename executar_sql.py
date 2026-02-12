"""
Autor: Thiago Mourão
Instagram: https://www.instagram.com/mouraoeguerin/
Data: 2026-02-11 17:27:00
"""

import subprocess
import sys

print("="*50)
print("INSTALAÇÃO DO SISTEMA DE CHAMADA")
print("="*50)
print()

# Ler o arquivo SQL
try:
    with open('criar_tabelas.sql', 'r', encoding='utf-8') as f:
        sql_content = f.read()
    print("✓ Arquivo SQL carregado com sucesso")
except Exception as e:
    print(f"✗ Erro ao ler arquivo SQL: {e}")
    sys.exit(1)

# Tentar instalar mysql-connector-python
print("\nInstalando biblioteca MySQL...")
try:
    subprocess.check_call([sys.executable, "-m", "pip", "install", "mysql-connector-python", "-q"])
    print("✓ Biblioteca MySQL instalada")
except:
    print("✗ Erro ao instalar biblioteca MySQL")
    print("\nPor favor, execute manualmente:")
    print("pip install mysql-connector-python")
    sys.exit(1)

# Agora importar e usar
try:
    import mysql.connector
    from mysql.connector import Error
    
    print("\nConectando ao banco de dados remoto...")
    
    conexao = mysql.connector.connect(
        host='186.209.113.101',
        database='cemaneto_chamadaded',
        user='cemaneto_chamadaded',
        password='QIHIt}bZa}[chA@P',
        charset='utf8mb4',
        collation='utf8mb4_unicode_ci'
    )
    
    if conexao.is_connected():
        print("✓ Conexão estabelecida com sucesso!\n")
        
        cursor = conexao.cursor()
        
        # Dividir e executar cada comando SQL
        comandos = sql_content.split(';')
        
        print("Executando comandos SQL...\n")
        
        for i, comando in enumerate(comandos):
            comando = comando.strip()
            if comando and not comando.startswith('--'):
                try:
                    cursor.execute(comando)
                    if 'CREATE TABLE' in comando.upper():
                        tabela = comando.split('TABLE')[1].split('(')[0].strip().split()[2] if 'IF NOT EXISTS' in comando.upper() else comando.split('TABLE')[1].split('(')[0].strip()
                        print(f"✓ Tabela criada/verificada")
                    elif 'INSERT INTO' in comando.upper():
                        print(f"✓ Usuário administrador criado")
                except Error as e:
                    if 'already exists' not in str(e).lower() and 'duplicate' not in str(e).lower():
                        print(f"⚠ Aviso: {e}")
        
        conexao.commit()
        
        print("\n" + "="*50)
        print("INSTALAÇÃO CONCLUÍDA COM SUCESSO!")
        print("="*50)
        print("\nTabelas criadas:")
        print("  • usuarios")
        print("  • alunos")
        print("  • cursos")
        print("  • matriculas")
        print("  • aulas")
        print("  • presencas")
        print("  • conclusoes")
        print("\nCredenciais de acesso:")
        print("  Email: admin@sistema.com")
        print("  Senha: admin123")
        print("\n⚠ IMPORTANTE: Altere a senha após o primeiro login!")
        
        cursor.close()
        conexao.close()
        print("\n✓ Conexão encerrada.")
        
except Error as e:
    print(f"\n✗ ERRO: {e}")
    sys.exit(1)
except ImportError as e:
    print(f"\n✗ Erro ao importar biblioteca: {e}")
    print("\nExecute: pip install mysql-connector-python")
    sys.exit(1)
