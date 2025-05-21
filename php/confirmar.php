<?php
require '../config.php';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die('Token inválido.');
}

$token = $_GET['token'];

try {
    $pdo = getDbConnection();

    // Busca usuário pelo token de confirmação e que ainda não está confirmado
    $stmt = $pdo->prepare("SELECT id, confirmado FROM usuarios WHERE token_confirmacao = ?");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        die('Token inválido ou usuário não encontrado.');
    }

    if ($usuario['confirmado']) {
        die('Usuário já confirmado.');
    }

    // Atualiza usuário para confirmado e limpa o token
    $stmt = $pdo->prepare("UPDATE usuarios SET confirmado = 1, token_confirmacao = NULL WHERE id = ?");
    $stmt->execute([$usuario['id']]);

    echo "Cadastro confirmado com sucesso! Você já pode fazer login.";
    header("Location: /login.html");
    exit;

} catch (Exception $e) {
    die("Erro ao confirmar cadastro: " . $e->getMessage());
}
