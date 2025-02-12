<?php
require_once __DIR__ . '/../includes/auto_check.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/permissions.php';

header('Content-Type: text/html; charset=UTF-8');

// Permitir apenas ADMIN e GESTOR acessar
verificarPermissao('consulta_lancamentos');

// Configuração da página
$pageTitle = 'Consulta de Lançamentos - Forecast System';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';

// Criar conexão com o banco
$db = new Database();
$conn = $db->getConnection();

setlocale(LC_TIME, 'pt_BR.UTF-8', 'Portuguese_Brazil.1252', 'portuguese');

// Capturar filtros do formulário
$mesReferencia = $_GET['mesReferencia'] ?? '';
$gestor = $_GET['gestor'] ?? '';
$empresa = $_GET['empresa'] ?? '';
$linha = $_GET['linha'] ?? '';
$modelo = $_GET['modelo'] ?? '';

// Função para buscar valores únicos no banco para filtros
function obterOpcoesFiltro($conn, $campo, $tabela) {
    $query = "SELECT DISTINCT $campo FROM $tabela WHERE $campo IS NOT NULL ORDER BY $campo ASC";
    $stmt = sqlsrv_query($conn, $query);
    $opcoes = [];

    if ($stmt === false) {
        die("Erro na consulta SQL: " . print_r(sqlsrv_errors(), true)); 
    }

    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $opcoes[] = $row[$campo];
    }

    return $opcoes;
}

// Buscar opções para os filtros diretamente no banco
$opcoesMeses = [];
$stmt = sqlsrv_query($conn, "
    SELECT FORMAT(mes_referencia, 'MMMM/yyyy', 'pt-BR') AS mes_referencia
    FROM Forecast_pcp
    GROUP BY FORMAT(mes_referencia, 'MMMM/yyyy', 'pt-BR'), CAST(mes_referencia AS DATE)
    ORDER BY CAST(mes_referencia AS DATE) DESC
");

if ($stmt === false) {
    die("Erro ao carregar os meses de referência: " . print_r(sqlsrv_errors(), true));
}

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    if (!in_array($row['mes_referencia'], $opcoesMeses)) {
        $opcoesMeses[] = mb_convert_case($row['mes_referencia'], MB_CASE_TITLE, "UTF-8");
    }
}


$opcoesGestores = obterOpcoesFiltro($conn, 'gestor', 'Forecast_pcp');
$opcoesEmpresas = obterOpcoesFiltro($conn, 'empresa', 'Forecast_pcp');
$opcoesLinhas = obterOpcoesFiltro($conn, 'LINHA', 'V_DEPARA_ITEM');
$opcoesModelos = obterOpcoesFiltro($conn, 'MODELO', 'V_DEPARA_ITEM');

// ✅ Corrigir a filtragem do Mês de Referência (YYYY-MM → YYYY-MM-01)
if (!empty($mesReferencia)) {
    $mesReferenciaFormatado = $mesReferencia . "-01"; 
}

// Construção da consulta SQL com filtros dinâmicos
$sql = "SELECT 
        CAST(f.mes_referencia AS DATE) AS mes_referencia, 
        f.cod_produto, 
        f.empresa, 
        f.quantidade,
        f.gestor,
        i.DESCITEM,
        i.LINHA,
        i.MODELO,
        i.STATUS
    FROM Forecast_pcp f
    LEFT JOIN V_DEPARA_ITEM i ON f.cod_produto = i.CODITEM";

$params = [];
$conditions = [];

if (!empty($mesReferencia)) {
    $conditions[] = "FORMAT(f.mes_referencia, 'MMMM/yyyy', 'pt-BR') = ?";
    $params[] = $mesReferencia;
}

if (!empty($gestor)) {
    $conditions[] = "f.gestor = ?";
    $params[] = $gestor;
}

if (!empty($empresa)) {
    $conditions[] = "f.empresa = ?";
    $params[] = $empresa;
}

if (!empty($linha)) {
    $conditions[] = "i.LINHA = ?";
    $params[] = $linha;
}

if (!empty($modelo)) {
    $conditions[] = "i.MODELO = ?";
    $params[] = $modelo;
}

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$sql .= " ORDER BY f.mes_referencia DESC";

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die("<div class='alert alert-danger'>Erro ao carregar os lançamentos: " . print_r(sqlsrv_errors(), true) . "</div>");
}

?>


<div class="content">
    <h2 class="mb-4"><i class="bi bi-search"></i> Consulta de Lançamentos</h2>

    <!-- Filtros -->
    <div class="card shadow-sm p-4 mb-4">
        <form method="GET" action="index.php" id="filterForm">
            <input type="hidden" name="page" value="consulta_lancamentos">
            <div class="row g-3">
                
                <div class="col-md-2">
                    <label for="mesReferencia" class="form-label fw-bold">Mês de Referência:</label>
                    <select class="form-select" id="mesReferencia" name="mesReferencia">
                        <option value="">Todos</option>
                        <?php foreach ($opcoesMeses as $mes): ?>
                            <option value="<?= htmlspecialchars($mes); ?>" <?= ($mesReferencia == $mes) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($mes); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="gestor" class="form-label fw-bold">Gestor:</label>
                    <select class="form-select" id="gestor" name="gestor">
                        <option value="">Todos</option>
                        <?php foreach ($opcoesGestores as $gest): ?>
                            <option value="<?= htmlspecialchars($gest); ?>" <?= ($gestor == $gest) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($gest); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="empresa" class="form-label fw-bold">Empresa:</label>
                    <select class="form-select" id="empresa" name="empresa">
                        <option value="">Todas</option>
                        <?php foreach ($opcoesEmpresas as $emp): ?>
                            <option value="<?= htmlspecialchars($emp); ?>" <?= ($empresa == $emp) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($emp); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="linha" class="form-label fw-bold">Linha:</label>
                    <select class="form-select" id="linha" name="linha">
                        <option value="">Todas</option>
                        <?php foreach ($opcoesLinhas as $ln): ?>
                            <option value="<?= htmlspecialchars($ln); ?>" <?= ($linha == $ln) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($ln); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="modelo" class="form-label fw-bold">Modelo:</label>
                    <select class="form-select" id="modelo" name="modelo">
                        <option value="">Todos</option>
                        <?php foreach ($opcoesModelos as $mod): ?>
                            <option value="<?= htmlspecialchars($mod); ?>" <?= ($modelo == $mod) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($mod); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-3 text-center">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-filter"></i> Aplicar Filtros
                </button>
                <a href="index.php?page=consulta_lancamentos" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Limpar Filtros
                </a>
                <a href="export_forecast.php?mesReferencia=<?= urlencode($mesReferencia) ?>&gestor=<?= urlencode($gestor) ?>&empresa=<?= urlencode($empresa) ?>&linha=<?= urlencode($linha) ?>&modelo=<?= urlencode($modelo) ?>"
                class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Exportar para Excel
                </a>
            </div>
        </form>
    </div>

    <!-- Tabela de Lançamentos -->
    <div class="card shadow-sm p-4">
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Mes Referência</th>
                    <th>Gestor</th>
                    <th>Empresa</th>
                    <th>SKU</th>
                    <th>Linha</th>
                    <th>Modelo</th>
                    <th>Descrição</th>
                    <th>Quantidade</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                    <td>
                        <?php 
                            if ($row['mes_referencia'] instanceof DateTime) {
                                echo mb_convert_case(strftime('%B/%Y', $row['mes_referencia']->getTimestamp()), MB_CASE_TITLE, "UTF-8");
                            } else {
                                echo "Data Inválida";
                            }
                        ?>
                    </td>
                        <td><?= htmlspecialchars($row['gestor']); ?></td>
                        <td><?= htmlspecialchars($row['empresa']); ?></td>
                        <td><?= htmlspecialchars($row['cod_produto']); ?></td>
                        <td><?= htmlspecialchars($row['LINHA']); ?></td>
                        <td><?= htmlspecialchars($row['MODELO']); ?></td>
                        <td><?= htmlspecialchars($row['DESCITEM']); ?></td>
                        <td><?= number_format($row['quantidade'], 0, ',', '.'); ?></td>
                        <td><?= htmlspecialchars($row['STATUS']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const linhaSelect = document.getElementById("linha");
    const modeloSelect = document.getElementById("modelo");

    // Atualizar modelos ao selecionar uma linha
    linhaSelect.addEventListener("change", function () {
        const linhaSelecionada = this.value;
        modeloSelect.innerHTML = '<option value="">Todos</option>'; // Resetar modelos

        if (linhaSelecionada) {
            fetch(`index.php?page=consulta_lancamentos&fetch=modelos&linha=${encodeURIComponent(linhaSelecionada)}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(modelo => {
                        const option = document.createElement("option");
                        option.value = modelo;
                        option.textContent = modelo;
                        modeloSelect.appendChild(option);
                    });
                });
        }
    });

    // Atualizar linhas ao selecionar um modelo
    modeloSelect.addEventListener("change", function () {
        const modeloSelecionado = this.value;
        linhaSelect.innerHTML = '<option value="">Todas</option>'; // Resetar linhas

        if (modeloSelecionado) {
            fetch(`index.php?page=consulta_lancamentos&fetch=linhas&modelo=${encodeURIComponent(modeloSelecionado)}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(linha => {
                        const option = document.createElement("option");
                        option.value = linha;
                        option.textContent = linha;
                        linhaSelect.appendChild(option);
                    });
                });
        }
    });
});
</script>


<?php include __DIR__ . '/../templates/footer.php'; ?>
