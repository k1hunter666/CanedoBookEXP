<?php
require '../config.php';
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $token = bin2hex(random_bytes(32));

    // Verifica campos obrigatórios
    if (empty($nome) || empty($email) || empty($senha)) {
        echo "Preencha todos os campos!";
        exit;
    }

    // Valida senha forte (mín. 8 caracteres, 1 maiúscula, 1 minúscula, 1 número, 1 caractere especial)
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $senha)) {
        echo "A senha deve ter no mínimo 8 caracteres, incluindo letra maiúscula, minúscula, número e símbolo.";
        exit;
    }

    try {
        $pdo = getDbConnection();

        // Verifica se e-mail já existe
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            echo "E-mail já cadastrado!";
            exit;
        }

        // Hash da senha
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

        // Insere usuário com token e confirmado = 0 (false)
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, token_confirmacao, confirmado) VALUES (?, ?, ?, ?, 0)");
        $stmt->execute([$nome, $email, $senhaHash, $token]);

        // Link para confirmação (ajuste conforme seu domínio/porta)
        $link = "http://localhost/php/confirmar.php?token=" . $token;

        // Configura e envia o email
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($email, $nome);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        $mail->Subject = 'Confirme seu cadastro, ' . $nome;
        $mail->Body = "Olá, $nome!<br><br>Clique no botão abaixo para confirmar seu cadastro:<br><br>
            <a href='$link' style='
                display: inline-block;
                background-color: #4CAF50;
                color: white;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 4px;'>Confirmar cadastro</a><br><br>
            Se não foi você, ignore este e-mail.";

        $mail->send();

        echo "Cadastro realizado! Verifique seu e-mail para confirmar.";
    } catch (Exception $e) {
        echo "Erro ao registrar: " . $e->getMessage();
    }
}
