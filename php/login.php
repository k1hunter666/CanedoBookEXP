<?php
require '../config.php';  // aqui deve existir a função getDbConnection()
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';

    if (!$email || !$senha) {
        echo "Por favor, preencha todos os campos.";
        exit;
    }

    try {
        $pdo = getDbConnection();

        // Prepara consulta para evitar SQL Injection
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verifica a senha (assumindo que está usando password_hash no cadastro)
            if (password_verify($senha, $user['senha'])) {
                // Dados da sessão
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['nome'] = $user['nome']; // nome da pessoa no banco
                $_SESSION['ultimo_acesso'] = time();

                header("Location: ../index.php");
                exit;
            } else {
                echo "Senha incorreta.";
                exit;
            }
        } else {
            echo "E-mail não encontrado.";
            exit;
        }
    } catch (PDOException $e) {
        echo "Erro no banco: " . $e->getMessage();
        exit;
    }
} else {
    // Se acessar direto o arquivo sem POST, redireciona pro login
    header("Location: ../login.html");
    exit;
}
