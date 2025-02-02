<?php
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/auto_check.php'; // Para capturar o usuário logado

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $regional = $_POST['regional'] ?? null;
    $gnv = $_POST['gnv'] ?? null;
    $nomeRegional = $_POST['nomeRegional'] ?? null;
    $analista = $_POST['analista'] ?? null;

    // Capturar usuário logado
    $usuarioLogado = $_SESSION['user_name'] ?? 'Desconhecido';

    // Capturar IP do usuário
    $ipUsuario = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Capturar Data/Hora Atual
    $dataEdicao = date('Y-m-d H:i:s');

    if (!$regional || !$gnv || !$nomeRegional || !$analista) {
        echo json_encode(["success" => false, "message" => "Todos os campos são obrigatórios."]);
        exit();
    }

    // Criar conexão com o banco
    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        error_log("Erro na conexão com o banco: " . print_r(sqlsrv_errors(), true));
        echo json_encode(["success" => false, "message" => "Erro na conexão com o banco.", "db_error" => sqlsrv_errors()]);
        exit();
    }

    // Atualizar os dados na tabela, incluindo usuário, data e IP da edição
    $sql = "UPDATE DW..DEPARA_COMERCIAL 
            SET GNV = ?, NomeRegional = ?, Analista = ?, 
                ultimo_usuario_editou = ?, 
                data_ultima_edicao = ?, 
                ip_ultima_edicao = ? 
            WHERE Regional = ?";
    $params = [$gnv, $nomeRegional, $analista, $usuarioLogado, $dataEdicao, $ipUsuario, $regional];

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        error_log("Erro SQL ao atualizar: " . print_r(sqlsrv_errors(), true));
        echo json_encode(["success" => false, "message" => "Erro ao atualizar no banco.", "sql_error" => sqlsrv_errors()]);
    } else {
        echo json_encode(["success" => true]);
    }

    exit();
}
?>
