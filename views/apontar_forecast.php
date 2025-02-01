<?php
require_once __DIR__ . '/../includes/auto_check.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/permissions.php';

// Permitir apenas ADMIN e GESTOR acessar
verificarPermissao('apontar_forecast');

// Configuração da página
$pageTitle = 'Apontar Forecast - Forecast System';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';

// Criar conexão com o banco
$db = new Database();
$conn = $db->getConnection();

// Forçar o locale para exibir meses em português
setlocale(LC_TIME, 'ptb.UTF-8', 'ptb', 'portuguese', 'portuguese_brazil');

// Obtém o usuário logado
$userName = $_SESSION['user_name'] ?? null;

if (!$userName) {
    die("<div class='alert alert-danger'>Erro: Usuário não identificado. Faça login novamente.</div>");
}

// Verificar se o usuário está cadastrado na tabela DEPARA_COMERCIAL como GNV, NomeRegional ou Analista
$sql = "SELECT Regional, GNV, NomeRegional, Analista FROM DW..DEPARA_COMERCIAL 
        WHERE GNV = ? OR NomeRegional = ? OR Analista = ?";
$params = [$userName, $userName, $userName];
$stmt = sqlsrv_query($conn, $sql, $params);

$gestorInfo = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Verifica se o usuário está habilitado (se retornou dados)
$usuarioHabilitado = ($gestorInfo !== null && $gestorInfo !== false);

$regionaisPermitidas = [];

if ($usuarioHabilitado) {
    // Se o usuário for encontrado, adiciona sua Regional à lista de opções disponíveis
    do {
        if (!empty($gestorInfo['Regional'])) {
            $regionaisPermitidas[] = $gestorInfo['Regional'];
        }
    } while ($gestorInfo = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC));
}

// Remove duplicatas, caso o usuário apareça em mais de um cargo
$regionaisPermitidas = array_unique($regionaisPermitidas);

// Se o usuário não tem regionais associadas, ele não poderá lançar Forecast
if (empty($regionaisPermitidas)) {
    $usuarioHabilitado = false;
}

// Mapeamento de Empresas para CD
$mapaCD = [
    '1001' => 'Matriz',
    '1002' => 'Feira de Santana'
];

// Capturar filtros selecionados pelo usuário
$cdSelecionado = $_POST['cd'] ?? '';
$regionalSelecionado = $_POST['regional'] ?? '';

$empresaSelecionada = isset($mapaCD[$cdSelecionado]) ? $cdSelecionado : null;

// 🔹 Determinar os próximos 3 meses
function obterProximosMeses($quantidade = 3) {
    $meses = [];
    $data = new DateTime('first day of next month'); // Começa do mês atual (corrigido para iniciar corretamente)

    for ($i = 0; $i < $quantidade; $i++) { // Agora começa do mês atual, não do próximo
        $meses[] = [
            'label' => ucfirst(strftime('%B de %Y', $data->getTimestamp())),
            'value' => $data->format('m/Y')
        ];
        $data->modify("+1 month"); // Avança um mês a cada iteração
    }
    return $meses;
}
$mesesForecast = obterProximosMeses();

// 🔹 Buscar os produtos ativos na carteira de pedidos
function obterQuantidadePorModelo($conn, $empresaSelecionada) {
    $quantidades = [];
    
    $sql = "SELECT 
            V.LINHA AS Linha_Produto,
            V.MODELO AS Modelo_Produto,
            SUM(C.Quantidade) AS Quantidade_Total
        FROM V_CARTEIRA_PEDIDOS C
        INNER JOIN V_DEPARA_ITEM V ON C.Cod_produto = V.MODELO
        WHERE V.STATUS = 'ATIVO'";

    $params = [];
    if ($empresaSelecionada) {
        $sql .= " AND C.Empresa = ?";
        $params[] = $empresaSelecionada;
    }

    $sql .= " GROUP BY V.LINHA, V.MODELO ORDER BY V.LINHA, V.MODELO";

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die("<div class='alert alert-danger'>Erro ao carregar dados da carteira.</div>");
    }

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $quantidades[] = $row;
    }

    return $quantidades;
}

$resultados = obterQuantidadePorModelo($conn, $empresaSelecionada);
?>

    <!-- Exibição das informações do usuário -->
     
    <?php 
    // Exibir mensagem de erro caso o usuário não tenha permissões
    if (!$usuarioHabilitado): ?>
        <div class="content">
            <h2 class="mb-4"><i class="bi bi-graph-up"></i> Apontar Forecast</h2>
            <div class="alert alert-danger">
                ⚠️ O usuário <strong><?= htmlspecialchars($userName); ?></strong> não está habilitado para realizar lançamentos no Forecast.
                Entre em contato com a equipe de TI.
            </div>
        </div>
        <?php include __DIR__ . '/../templates/footer.php'; ?>
        <?php exit(); // Interrompe a execução do restante do código ?>
    <?php endif; ?>

    
    <div class="content">
    <h2 class="mb-4"><i class="bi bi-graph-up"></i> Apontar Forecast</h2>

    <!-- Exibição das informações do usuário -->
    <div class="alert alert-info">
        <strong class="mb-5"><i class="bi bi-person-lines-fill"></i> INFORMAÇÕES DO GESTOR </strong><br>
        <strong>Usuário:</strong> <?= htmlspecialchars($userName); ?> <br>
        <strong>Código de Gestor:</strong> <?= htmlspecialchars($gestorInfo['Regional']); ?>
    </div>

    
    <!-- Filtro para selecionar o CD -->
    <?php if ($usuarioHabilitado): ?>
        <!-- Filtro para selecionar o CD -->
        <form action="index.php?page=apontar_forecast" method="POST" id="filterForm">
            <div class="row g-3">
            <div class="col-md-4">
            <label for="cd" class="form-label fw-bold">Centro de Distribuição:</label>
            <select class="form-select" id="cd" name="cd" required>
                <?php if (!$cdSelecionado): ?> 
                    <option value="" selected disabled>Selecione o CD</option> 
                <?php endif; ?>
                <?php foreach ($mapaCD as $key => $value): ?>
                    <option value="<?= $key; ?>" <?= ($cdSelecionado == $key) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($value); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span id="mensagemCD" style="color: red; font-weight: bold; display: <?= $cdSelecionado ? 'none' : 'block'; ?>;">
                Informe um CD para apontar o forecast.
            </span>
        </div>

        <div class="col-md-4">
            <label for="regional" class="form-label fw-bold">Código Regional:</label>
            <select class="form-select" id="regional" name="regional" required>
                <?php if (!$regionalSelecionado): ?>
                    <option value="" selected disabled>Selecione o Código Regional</option>
                <?php endif; ?>
                <?php foreach ($regionaisPermitidas as $regional): ?>
                    <option value="<?= htmlspecialchars($regional); ?>" <?= ($regionalSelecionado == $regional) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($regional); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <span id="mensagemRegional" style="color: red; font-weight: bold; display: <?= $regionalSelecionado ? 'none' : 'block'; ?>;">
                Informe um Código Regional para apontar o forecast.
            </span>
        </div>
            </div>
        </form>

        <!-- Mensagens -->
        <div id="updateMessage" class="alert alert-warning text-center mt-3" style="display: none;">
            Atualizando dados...
        </div>
        <div id="successMessage" class="alert alert-success text-center mt-3" style="display: none;">
            Dados atualizados com sucesso!
        </div>

        <!-- Formulário para envio dos dados de forecast -->
        <form action="index.php?page=process_forecast" method="POST" id="forecastForm">
            <input type="hidden" name="cd" value="<?= htmlspecialchars($cdSelecionado); ?>">
            <input type="hidden" name="regional" value="<?= htmlspecialchars($regionalSelecionado); ?>">

            <div class="card shadow-sm p-3 mt-4 d-flex flex-column">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Linha</th>
                            <th>Modelo</th>
                            <th>Carteira</th>
                            <?php foreach ($mesesForecast as $mes): ?>
                                <th><?= htmlspecialchars($mes['label']); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['Linha_Produto'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($row['Modelo_Produto'] ?? 'N/A'); ?></td>
                                <td><?= number_format($row['Quantidade_Total'], 0, ',', '.'); ?></td>
                                
                                <?php foreach ($mesesForecast as $mes): ?>
                                    <td>
                                        <input type="number" class="form-control form-control-sm forecast-input" 
                                            name="forecast[<?= htmlspecialchars($row['Modelo_Produto']); ?>][<?= $mes['value']; ?>]" 
                                            min="0"
                                            value="0"
                                            <?= !$cdSelecionado ? 'disabled' : ''; ?>>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3 text-center">
                <button type="submit" id="enviarForecast" class="btn btn-primary w-50" <?= !$cdSelecionado ? 'disabled' : ''; ?>>
                    <i class="bi bi-send"></i> Enviar Forecast
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const cdSelect = document.getElementById("cd");
    const regionalSelect = document.getElementById("regional");
    const mensagemCD = document.getElementById("mensagemCD");
    const mensagemRegional = document.getElementById("mensagemRegional");
    const forecastInputs = document.querySelectorAll(".forecast-input");
    const enviarForecastButton = document.getElementById("enviarForecast");
    const updateMessage = document.getElementById("updateMessage");
    const successMessage = document.getElementById("successMessage");
    const form = document.getElementById("filterForm");

    function atualizarEstadoCampos() {
        const cdSelecionado = cdSelect.value !== "";
        const regionalSelecionado = regionalSelect.value !== "";

        const habilitarForm = cdSelecionado && regionalSelecionado;
        forecastInputs.forEach(input => input.disabled = !habilitarForm);
        enviarForecastButton.disabled = !habilitarForm;
        
        // Exibir ou ocultar mensagens de erro conforme necessário
        mensagemCD.style.display = cdSelecionado ? "none" : "block";
        mensagemRegional.style.display = regionalSelecionado ? "none" : "block";
    }

    cdSelect.addEventListener("change", function () {
        updateMessage.style.display = "block"; 
        successMessage.style.display = "none"; 
        form.submit();
    });

    regionalSelect.addEventListener("change", function () {
        updateMessage.style.display = "block"; 
        successMessage.style.display = "none"; 
        form.submit();
    });

    atualizarEstadoCampos();
});
</script>