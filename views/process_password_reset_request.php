<?php
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    $db = new Database();
    $conn = $db->getConnection();

    // Verifica se o e-mail existe no banco de dados
    $sql = "SELECT id FROM users WHERE email = ?";
    $params = array($email);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false || sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC) === null) {
        $_SESSION['error_message'] = "E-mail não encontrado.";
        header("Location: index.php?page=password_reset_request");
        exit();
    }

      // Gere o token e a data de expiração
    $resetToken = bin2hex(random_bytes(32));
    $expirationTime = (new DateTime('+1 hour'))->format('d-m-Y H:i:s'); // Formato ajustado

    // Exibir valores para depuração
    var_dump($resetToken, $expirationTime, $email);

    // Salve o token e a expiração no banco
    $sql = "UPDATE users SET reset_token = ?, reset_token_expiration = ? WHERE email = ?";
    $params = [$resetToken, $expirationTime, $email];
    $result = sqlsrv_query($conn, $sql, $params);

    if ($result === false) {
        die("Erro ao salvar o token no banco: " . print_r(sqlsrv_errors(), true));
    }

    echo "Token salvo com sucesso!";
}

    // Configurar e enviar o e-mail com o link de redefinição de senha
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Servidor SMTP do Gmail
        $mail->SMTPAuth = true;
        $mail->Username = 'wendrew.gomes@colormaq.com.br'; // Seu e-mail do Gmail
        $mail->Password = 'gdoc dfzb nnnt whzn'; // Senha do Gmail ou senha de aplicativo
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Tipo de segurança
        $mail->Port = 587; // Porta SMTP do Gmail
    
        $mail->setFrom('wendrew.gomes@colormaq.com.br', 'T.I. Colormaq'); // E-mail e nome de quem envia
        $mail->addAddress($email); // E-mail do destinatário

        // Configurar o charset para UTF-8
        $mail->CharSet = 'UTF-8'; // Define o charset do e-mail como UTF-8

        $mail->isHTML(true);
        $mail->Subject = 'Recuperação de Senha - T.I. Colormaq';
        $mail->Body = "Olá,<br><br> Clique no link abaixo para redefinir sua senha:<br>
            <a href='http://localhost/forecast/public/index.php?page=password_reset_form&token=$resetToken'>
            Redefinir senha</a><br><br> Este link é válido por 1 hora.";
    
        $mail->send();
        $_SESSION['success_message'] = "E-mail de recuperação enviado!";
        header("Location: index.php?page=login");
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erro ao enviar e-mail: {$mail->ErrorInfo}";
        header("Location: index.php?page=password_reset_request");
        exit();
    }

?>
