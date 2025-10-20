<?php
// Arquivo: /config.php

// Defina o ambiente atual aqui: 'development' ou 'production'
// Mude para 'production' quando for fazer o deploy do site.
define('APP_ENV', 'production'); 

if (APP_ENV === 'production') {
    // Carrega as configurações de produção
    return require_once __DIR__ . '/config.prod.php';
} else {
    // Carrega as configurações de desenvolvimento
    return require_once __DIR__ . '/config.dev.php';
}