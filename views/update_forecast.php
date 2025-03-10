<?php
require_once __DIR__ . '/../includes/auto_check.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/permissions.php';

// Permitir apenas ADMIN e GESTOR acessar
verificarPermissao('apontar_forecast');

// Verificar se a solicitação é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Método não permitido
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit();
}

// Receber os dados do POST
$id = $_POST['id'] ?? null;
$novoValor = $_POST['novo_valor'] ?? null;

// Capturar usuário logado
$usuarioLogado = $_SESSION['user_name'] ?? 'Desconhecido';

// Capturar IP do usuário
$ipUsuario = $_SERVER['REMOTE_ADDR'] ?? 'Desconhecido';

// Validar os dados recebidos
if (!$id || $novoValor === null || !is_numeric($novoValor) || $novoValor < 0) {
    http_response_code(400); // Requisição inválida
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit();
}

// Conectar ao banco
$db = new Database();
$conn = $db->getConnection();

// Atualizar a quantidade no banco e registrar usuário/IP/data da alteração
$sql = "UPDATE forecast_entries 
        SET quantidade = ?, 
            ultimo_usuario_editou = ?, 
            data_ultima_alteracao = GETDATE(), 
            ip_ultima_alteracao = ?
        WHERE id = ?";
$params = [$novoValor, $usuarioLogado, $ipUsuario, $id];

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    http_response_code(500); // Erro interno do servidor
    error_log(print_r(sqlsrv_errors(), true)); // Log de erro
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o banco de dados']);
    exit();
}

// Retornar sucesso
echo json_encode(['success' => true]);
exit();
?>
