<?php
/*require_once __DIR__ . '/../includes/auto_check.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/permissions.php';

// Permitir apenas ADMIN e GESTOR acessar
verificarPermissao('apontar_forecast');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $forecastData = $_POST['forecast'] ?? [];
    $empresa = $_POST['cd'] ?? null;

    // Verificar se o CD foi selecionado
    if (!$empresa) {
        $_SESSION['error_message'] = "Por favor, selecione um Centro de Distribuição antes de enviar.";
        header("Location: index.php?page=apontar_forecast");
        exit();
    }

    // Criar conexão com o banco
    $db = new Database();
    $conn = $db->getConnection();

    $errosSQL = []; // Armazena erros de execução SQL

    // Inserir dados na tabela forecast_entries
    foreach ($forecastData as $modelo => $meses) {
        foreach ($meses as $mesReferencia => $quantidade) {
            // Garantir que qualquer campo vazio seja tratado como 0
            $quantidade = (!empty($quantidade) && is_numeric($quantidade)) ? (int)$quantidade : 0;

            // Registrar log para depuração
            error_log("Tentando inserir: Modelo=$modelo, Mês=$mesReferencia, Empresa=$empresa, Quantidade=$quantidade");

            // Validar tamanhos dos campos
            if (mb_strlen($modelo) > 200) {
                error_log("Erro: Modelo '$modelo' excede o limite de 200 caracteres.");
                continue;
            }
            if (mb_strlen($mesReferencia) > 20) {
                error_log("Erro: Mês '$mesReferencia' excede o limite de 20 caracteres.");
                continue;
            }
            if (mb_strlen($empresa) > 20) {
                error_log("Erro: Empresa '$empresa' excede o limite de 20 caracteres.");
                continue;
            }

            // Inserir no banco
            $sql = "INSERT INTO forecast_entries (data_lancamento, modelo_produto, mes_referencia, empresa, quantidade)
                    VALUES (GETDATE(), ?, ?, ?, ?)";
            $params = [$modelo, $mesReferencia, $empresa, $quantidade];
            $stmt = sqlsrv_query($conn, $sql, $params);

            if ($stmt === false) {
                $errosSQL[] = sqlsrv_errors();
                error_log("Erro ao salvar modelo: $modelo, mês: $mesReferencia, quantidade: $quantidade");
                error_log(print_r(sqlsrv_errors(), true));
            }
        }
    }

    // Verificar se houve erros durante a execução das consultas
    if (!empty($errosSQL)) {
        $_SESSION['error_message'] = "Houve erros ao salvar o forecast. Verifique os logs.";
        header("Location: index.php?page=apontar_forecast");
        exit();
    }

    // Redirecionamento para a página de consulta
    $_SESSION['success_message'] = "Forecast enviado com sucesso!";
    header("Location: index.php?page=consulta_lancamentos");
    exit();
}*/
?>
<?php
require_once __DIR__ . '/../includes/auto_check.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/permissions.php';

// Permitir apenas ADMIN e GESTOR acessar
verificarPermissao('apontar_forecast');

// Verifica se os dados foram enviados via POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Capturar o Centro de Distribuição
    $cdSelecionado = $_POST['cd'] ?? null;
    
    // Verificar se um CD foi selecionado
    if (!$cdSelecionado) {
        $_SESSION['error_message'] = "Erro: Nenhum Centro de Distribuição selecionado.";
        header("Location: index.php?page=apontar_forecast");
        exit();
    }

    // Criar conexão com o banco
    $db = new Database();
    $conn = $db->getConnection();

    // Capturar os dados do formulário
    $forecastData = $_POST['forecast'] ?? [];

    // Array para armazenar erros de inserção
    $errosSQL = [];

    // Inserir cada registro no banco de dados
    foreach ($forecastData as $modelo => $meses) {
        foreach ($meses as $mesReferencia => $quantidade) {
            // Converter valores vazios ou inválidos para zero
            $quantidade = is_numeric($quantidade) ? intval($quantidade) : 0;

            // Debug - Log no servidor para verificar os valores recebidos
            error_log("Inserindo: CD={$cdSelecionado}, Modelo={$modelo}, Mês={$mesReferencia}, Quantidade={$quantidade}");

            // Query de inserção
            $sql = "INSERT INTO forecast_entries (data_lancamento, modelo_produto, mes_referencia, empresa, quantidade)
                    VALUES (GETDATE(), ?, ?, ?, ?)";

            // Parâmetros da query
            $params = [$modelo, $mesReferencia, $cdSelecionado, $quantidade];

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
