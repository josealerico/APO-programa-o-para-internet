# Gerenciador Logístico — LogiFlow

Projeto acadêmico para a disciplina **Fundamentos de Programação para Internet (UNIPAR)**, desenvolvido com PHP, MySQL, HTML, CSS, JavaScript e Bootstrap.

## Funcionalidades
- Painel com indicadores de produtos, endereços, transportadores e fretes.
- Cadastro, edição e exclusão de produtos.
- Cadastro, edição e exclusão de endereços de origem/destino.
- Cadastro, edição e exclusão de transportadores.
- Criação de fretes relacionando origem, destino, transportador e um ou mais produtos.
- Relacionamento de um frete com vários produtos por meio da tabela `frete_produtos`.
- Atualização do status dos fretes.
- Consulta de fretes por data e exportação do relatório em CSV.
- Interface responsiva com Bootstrap 5.

## Requisitos
- XAMPP com Apache, PHP 8.1+ e MySQL/MariaDB.
- Navegador atualizado.
- Internet para carregar o CSS do Bootstrap via CDN (a aplicação também funciona sem o CDN, mas sem o estilo do framework).

## Instalação no XAMPP
1. Extraia a pasta `gerenciador_logistico_unipar` em `C:\xampp\htdocs\`.
2. Abra o XAMPP Control Panel e inicie **Apache** e **MySQL**.
3. Acesse `http://localhost/phpmyadmin`.
4. Importe o arquivo `database.sql` (aba **Importar**). Ele cria o banco `gerenciador_logistico`.
5. Confira as credenciais em `config.php`. No XAMPP padrão, o usuário costuma ser `root` e a senha vazia.
6. Abra `http://localhost/gerenciador_logistico_unipar/`.

## Roteiro rápido de teste
1. Cadastre dois endereços (por exemplo, Depósito em Cascavel/PR e Cliente em Toledo/PR).
2. Cadastre um transportador.
3. Cadastre dois produtos.
4. Acesse **Fretes**, selecione origem, destino e transportador e adicione os dois produtos ao mesmo frete.
5. Altere o status para “Em trânsito” ou “Concluído”.
6. Em **Relatórios**, escolha a data do frete e clique em **Baixar CSV**.
