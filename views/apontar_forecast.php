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
    do {
        if (!empty($gestorInfo['Regional'])) {
            $regionaisPermitidas[] = $gestorInfo['Regional'];
        }
    } while ($gestorInfo = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC));
}
$regionaisPermitidas = array_unique($regionaisPermitidas);
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

// 🔹 Determinar os próximos 3 meses (o próximo mês é o definitivo)
function obterProximosMeses($quantidade = 3) {
    $meses = [];
    $data = new DateTime('first day of next month');
    for ($i = 0; $i < $quantidade; $i++) {
        $meses[] = [
            'label' => ucfirst(strftime('%B de %Y', $data->getTimestamp())),
            'value' => $data->format('m/Y')
        ];
        $data->modify("+1 month");
    }
    return $meses;
}
$mesesForecast = obterProximosMeses();

// 🔹 Verificar se já existe forecast definitivo para o próximo mês (sem levar em conta o modelo)
$forecastExiste = false;
if (!empty($cdSelecionado) && !empty($regionalSelecionado)) {
    $mesReferencia = $mesesForecast[0]['value']; // próximo mês
    $sqlForecast = "SELECT 1 FROM forecast_entries 
                    WHERE empresa = ? AND cod_gestor = ? AND mes_referencia = ? AND finalizado = 1";
    $paramsForecast = [$cdSelecionado, $regionalSelecionado, $mesReferencia];
    $stmtForecast = sqlsrv_query($conn, $sqlForecast, $paramsForecast);
    if ($stmtForecast !== false && sqlsrv_fetch_array($stmtForecast, SQLSRV_FETCH_ASSOC)) {
        $forecastExiste = true;
    }
}

// 🔹 Buscar os produtos ativos na carteira de pedidos – agora usando LEFT JOIN para trazer todos os produtos ativos
function obterQuantidadePorModelo($conn, $empresaSelecionada, $regionalSelecionado) {
    $quantidades = [];
    $sql = "SELECT 
                V.LINHA AS Linha_Produto,
                V.MODELO AS Modelo_Produto,
                ISNULL(SUM(C.Quantidade), 0) AS Quantidade_Total
            FROM V_DEPARA_ITEM V
            LEFT JOIN V_CARTEIRA_PEDIDOS C ON C.Cod_produto = V.MODELO";
    $params = [];
    if ($empresaSelecionada) {
        $sql .= " AND C.Empresa = ?";
        $params[] = $empresaSelecionada;
    }
    if (!empty($regionalSelecionado)) {
        $sql .= " AND C.Cod_regional = ?";
        $params[] = $regionalSelecionado;
    }
    $sql .= " WHERE V.STATUS = 'ATIVO'
              GROUP BY V.LINHA, V.MODELO
              ORDER BY V.LINHA, V.MODELO";
    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt === false) {
        die("<div class='alert alert-danger'>Erro ao carregar dados da carteira.</div>");
    }
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $quantidades[] = $row;
    }
    return $quantidades;
}
$resultados = obterQuantidadePorModelo($conn, $empresaSelecionada, $regionalSelecionado);
?>

<div class="content">
    <h2 class="mb-4"><i class="bi bi-graph-up"></i> Apontar Forecast</h2>

    <!-- Filtro para selecionar o CD e Regional -->
    <?php if ($usuarioHabilitado): ?>
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

        <!-- Mensagens de atualização -->
        <div id="updateMessage" class="alert alert-warning text-center mt-3" style="display: none;">
            Atualizando dados...
        </div>
        <div id="successMessage" class="alert alert-success text-center mt-3" style="display: none;">
            Dados atualizados com sucesso!
        </div>

        <?php if (empty($cdSelecionado) || empty($regionalSelecionado) || $forecastExiste): ?>
            <div class="text-center mt-3">
                <?php if($forecastExiste): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-emoji-smile"></i> Já existe apontamento de forecast para o regional selecionado. Caso queira editar, acesse o histórico de apontamentos, <a href="index.php?page=historico_forecast">clique aqui</a>.
                    </div>
                <?php endif; ?>
                <img src="../public/assets/img/apontar forecast.jpg" alt="Apontar Forecast" class="img-fluid" />
            </div>
        <?php else: ?>
            <form action="index.php?page=process_forecast" method="POST" id="forecastForm">
                <input type="hidden" name="cd" value="<?= htmlspecialchars($cdSelecionado); ?>">
                <input type="hidden" name="regional" value="<?= htmlspecialchars($regionalSelecionado); ?>">
                <input type="hidden" name="usuario_apontamento" value="<?= htmlspecialchars($userName); ?>">
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
                                                min="0" value="0">
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 text-center">
                    <button type="submit" id="enviarForecast" class="btn btn-primary w-50">
                        <i class="bi bi-send"></i> Enviar Forecast
                    </button>
                </div>
            </form>
        <?php endif; ?>
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
        if(enviarForecastButton){
            enviarForecastButton.disabled = !habilitarForm;
        }
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
