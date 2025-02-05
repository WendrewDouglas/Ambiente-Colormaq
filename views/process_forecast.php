<?php
require_once __DIR__ . '/../includes/auto_check.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/permissions.php';

// Permitir apenas ADMIN e GESTOR acessar
verificarPermissao('apontar_forecast');

// Capturar IP de quem está apontando
function obterIPUsuario() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}
$ipUsuario = obterIPUsuario();

// Verifica se os dados foram enviados via POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Capturar o Centro de Distribuição, Código Regional e usuário
    $cdSelecionado = $_POST['cd'] ?? null;
    $regionalSelecionado = $_POST['regional'] ?? null;
    $usuarioApontamento = $_POST['usuario_apontamento'] ?? null;

    // Verificar se os filtros obrigatórios e o usuário foram enviados
    if (!$cdSelecionado || !$regionalSelecionado || !$usuarioApontamento) {
        $_SESSION['error_message'] = "Erro: Centro de Distribuição, Código Regional ou Usuário não identificados.";
        header("Location: index.php?page=apontar_forecast");
        exit();
    }

    // Criar conexão com o banco
    $db = new Database();
    $conn = $db->getConnection();

    // Calcular o mês de referência: sempre o próximo mês
    $data = new DateTime('first day of next month');
    $mesReferencia = $data->format('m/Y');

    // Verificar se já existe forecast para essa combinação
    $sqlCheck = "SELECT 1 FROM forecast_entries WHERE empresa = ? AND cod_gestor = ? AND mes_referencia = ?";
    $paramsCheck = [$cdSelecionado, $regionalSelecionado, $mesReferencia];
    $stmtCheck = sqlsrv_query($conn, $sqlCheck, $paramsCheck);
    if ($stmtCheck !== false && sqlsrv_fetch_array($stmtCheck, SQLSRV_FETCH_ASSOC)) {
        $_SESSION['error_message'] = "Já existe apontamento de forecast do " . htmlspecialchars($regionalSelecionado) . " para o " . htmlspecialchars($cdSelecionado) . " para o mês de referência " . htmlspecialchars($mesReferencia) . ".";
        header("Location: index.php?page=apontar_forecast");
        exit();
    }

    // Capturar os dados do formulário
    $forecastData = $_POST['forecast'] ?? [];
    $dataHoraAtual = date('Y-m-d H:i:s');

    // Array para armazenar erros de inserção
    $errosSQL = [];

    // Inserir cada registro no banco de dados
    foreach ($forecastData as $modelo => $meses) {
        foreach ($meses as $mesReferenciaInput => $quantidade) {
            // Converter valores vazios ou inválidos para zero
            $quantidade = is_numeric($quantidade) ? intval($quantidade) : 0;
            error_log("Inserindo: CD={$cdSelecionado}, Regional={$regionalSelecionado}, UsuarioApontamento={$usuarioApontamento}, IpUsuario={$ipUsuario}, Modelo={$modelo}, Mês={$mesReferenciaInput}, Quantidade={$quantidade}");
            $sql = "INSERT INTO forecast_entries (data_lancamento, modelo_produto, mes_referencia, empresa, cod_gestor, usuario_apontamento, ip_usuario, quantidade)
                    VALUES (GETDATE(), ?, ?, ?, ?, ?, ?, ?)";
            $params = [$modelo, $mesReferenciaInput, $cdSelecionado, $regionalSelecionado, $usuarioApontamento, $ipUsuario, $quantidade];
            $stmt = sqlsrv_query($conn, $sql, $params);
            if ($stmt === false) {
                $errosSQL[] = sqlsrv_errors();
                error_log("Erro ao salvar: " . print_r(sqlsrv_errors(), true));
            }
        }
    }

    if (!empty($errosSQL)) {
        $_SESSION['error_message'] = "Houve erros ao salvar os dados. Verifique os logs.";
    } else {
        $_SESSION['success_message'] = "Forecast enviado com sucesso!";
    }
    header("Location: index.php?page=apontar_forecast");
    exit();
} else {
    header("Location: index.php?page=apontar_forecast");
    exit();
}
?>
