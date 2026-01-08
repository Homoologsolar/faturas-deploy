# Configuração do Ambiente de Desenvolvimento Local (Solar Faturas)

Este guia documenta como configurar o projeto localmente para depuração e desenvolvimento, permitindo visualizar erros detalhados (ex: status 500).

## 1. Pré-requisitos
* **XAMPP** (ou WAMP/Laragon/Docker) para rodar PHP e MySQL.
* **Git** (opcional, para clonar).
* **Navegador** (Chrome/Edge) para inspecionar requisições.

## 2. Configuração do Banco de Dados
1.  Inicie o **MySQL** no seu painel do XAMPP.
2.  Acesse o phpMyAdmin (geralmente `http://localhost/phpmyadmin`).
3.  Crie um novo banco de dados chamado: `u103839941_faturas` (ou outro nome, desde que atualize a config).
4.  Importe o arquivo `u103839941_faturas.sql` localizado na raiz do projeto.
    * *Nota:* Certifique-se de que a tabela `faturas` possui a coluna `creditos` e que os Triggers foram importados corretamente.

## 3. Configuração do Backend (API)
O sistema utiliza um seletor de ambiente. Você precisa criar o arquivo de configuração local que é ignorado pelo Git.

### Passo 3.1: Criar `api/config.dev.php`
Na pasta `api/`, crie um arquivo chamado `config.dev.php` com o seguinte conteúdo (ajuste a senha se o seu root tiver senha):

```php
<?php
// api/config.dev.php

return [
    'db_host' => 'localhost',
    'db_name' => 'u103839941_faturas', // Nome do banco que você criou
    'db_user' => 'root',              // Usuário padrão do XAMPP
    'db_pass' => ''                   // Senha padrão do XAMPP (vazia)
];
?>