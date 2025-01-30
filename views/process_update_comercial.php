<?php
require_once __DIR__ . '/../includes/db_connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $regional = $_POST['regional'] ?? null;
    $gnv = $_POST['gnv'] ?? null;
    $nomeRegional = $_POST['nomeRegional'] ?? null;
    $analista = $_POST['analista'] ?? null;

    if (!$regional || !$gnv || !$nomeRegional || !$analista) {
        echo json_encode(["success" => false, "message" => "Todos os campos são obrigatórios."]);
        exit();
    }

    // Criar conexão com o banco
    $db = new Database();
    $conn = $db->getConnection();

    // Atualizar os dados na tabela
    $sql = "UPDATE DW..DEPARA_COMERCIAL 
            SET GNV = ?, NomeRegional = ?, Analista = ?
            WHERE Regional = ?";
    $params = [$gnv, $nomeRegional, $analista, $regional];
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        echo json_encode(["success" => false, "message" => "Erro ao atualizar no banco."]);
    } else {
        echo json_encode(["success" => true]);
    }

    exit();
}
?>
