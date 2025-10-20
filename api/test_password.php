<?php
// faturas/api/test_password.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'Database.php';

echo "<h1>Teste de Verificação de Senha</h1>";

$usuario_para_testar = 'Homolog';
$senha_para_testar = 'Homo1007@';

try {
    $database = Database::getInstance();
    $pdo = $database->getConnection();

    echo "<p>Buscando usuário: '<strong>{$usuario_para_testar}</strong>'...</p>";

    $stmt = $pdo->prepare("SELECT * FROM administradores WHERE usuario = ?");
    $stmt->execute([$usuario_para_testar]);
    $user = $stmt->fetch();

    if ($user) {
        echo "<p>Usuário encontrado!</p>";
        echo "<p>Hash do banco: " . htmlspecialchars($user['senha']) . "</p>";
        echo "<p>Testando a senha: '<strong>{$senha_para_testar}</strong>'...</p>";

        if (password_verify($senha_para_testar, $user['senha'])) {
            echo "<h2 style='color: green;'>SUCESSO: A senha corresponde ao hash!</h2>";
            echo "<p>O seu sistema de login deveria funcionar. Verifique se não há erros de digitação na tela de login.</p>";
        } else {
            echo "<h2 style='color: red;'>FALHA: A senha NÃO corresponde ao hash!</h2>";
            echo "<p><strong>Solução:</strong> O hash no seu banco de dados está incorreto. Execute novamente o comando SQL de INSERT que forneci anteriormente para corrigir o hash.</p>";
        }
    } else {
        echo "<h2 style='color: red;'>ERRO: Usuário '{$usuario_para_testar}' não foi encontrado na tabela 'administradores'.</h2>";
    }

} catch (Exception $e) {
    echo "<h2 style='color: red;'>ERRO DE CONEXÃO:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>