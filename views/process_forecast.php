<?php
require_once __DIR__ . '/../includes/auto_check.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/permissions.php';

// Permitir apenas ADMIN e GESTOR acessar
verificarPermissao('apontar_forecast');

//Capturar IP de quem está apontando
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
    // Capturar o Centro de Distribuição
    $cdSelecionado = $_POST['cd'] ?? null;
    $regionalSelecionado = $_POST['regional'] ?? null;
    $usuarioApontamento = $_POST['usuario_apontamento'] ?? null;

    
// Verificar se ambos os filtros obrigatórios e usuário foram enviados
if (!$cdSelecionado || !$regionalSelecionado || !$usuarioApontamento) {
    $_SESSION['error_message'] = "Erro: Centro de Distribuição, Código Regional ou Usuário não identificados.";
    header("Location: index.php?page=apontar_forecast");
    exit();
}
    // Criar conexão com o banco
    $db = new Database();
    $conn = $db->getConnection();

    // Capturar os dados do formulário
    $forecastData = $_POST['forecast'] ?? [];
    $dataHoraAtual = date('Y-m-d H:i:s'); // Obtém data e hora do servidor PHP

    // Array para armazenar erros de inserção
    $errosSQL = [];

    // Inserir cada registro no banco de dados
    foreach ($forecastData as $modelo => $meses) {
        foreach ($meses as $mesReferencia => $quantidade) {
            // Converter valores vazios ou inválidos para zero
            $quantidade = is_numeric($quantidade) ? intval($quantidade) : 0;

            // Debug - Log no servidor para verificar os valores recebidos
            error_log("Inserindo: CD={$cdSelecionado}, Regional={$regionalSelecionado}, UsuarioApontamento={$usuarioApontamento}, IpUsuario={$ipusuario}, Modelo={$modelo}, Mês={$mesReferencia}, Quantidade={$quantidade}");

            $sql = "INSERT INTO forecast_entries (data_lancamento, modelo_produto, mes_referencia, empresa, cod_gestor, usuario_apontamento, ip_usuario, quantidade)
            VALUES (GETDATE(), ?, ?, ?, ?, ?, ?, ?)";
            $params = [$modelo, $mesReferencia, $cdSelecionado, $regionalSelecionado, $usuarioApontamento, $ipUsuario, $quantidade];
            
            // Executar a query
            $stmt = sqlsrv_query($conn, $sql, $params);

            // Se der erro, armazenar a mensagem para depuração
            if ($stmt === false) {
                $errosSQL[] = sqlsrv_errors();
                error_log("Erro ao salvar: " . print_r(sqlsrv_errors(), true));
            }
        }
    }

    // Verificar se houve erros
    if (!empty($errosSQL)) {
        $_SESSION['error_message'] = "Houve erros ao salvar os dados. Verifique os logs.";
    } else {
        $_SESSION['success_message'] = "Forecast enviado com sucesso!";
    }

    // Redirecionar para a página de forecast
    header("Location: index.php?page=apontar_forecast");
    exit();
} else {
    // Se o acesso for direto sem POST, redireciona para a página principal
    header("Location: index.php?page=apontar_forecast");
    exit();
}




?>
