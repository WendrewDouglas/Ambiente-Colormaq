<?php
require_once __DIR__ . '/../includes/auto_check.php';
require_once __DIR__ . '/../includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['id'];
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $role = $_POST['role'];

    if (empty($name) || empty($email) || empty($role)) {
        $_SESSION['error_message'] = "Todos os campos são obrigatórios.";
        header("Location: index.php?page=edit_user&id=" . $user_id);
        exit();
    }

    $db = new Database();
    $conn = $db->getConnection();
    
    $sql = "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?";
    $params = array($name, $email, $role, $user_id);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        $_SESSION['error_message'] = "Erro ao atualizar usuário.";
    } else {
        $_SESSION['success_message'] = "Usuário atualizado com sucesso!";
    }

    header("Location: index.php?page=users");
    exit();
}
?>
