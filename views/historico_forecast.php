<?php
require_once __DIR__ . '/../includes/auto_check.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/permissions.php';

// Permitir apenas ADMIN e GESTOR acessar
verificarPermissao('apontar_forecast');

// Configuração da página
$pageTitle = 'Histórico de Apontamentos - Forecast System';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';

// Criar conexão com o banco
$db = new Database();
$conn = $db->getConnection();

// Capturar filtros do formulário
$mesReferencia = $_GET['mesReferencia'] ?? '';
$gestor = $_GET['gestor'] ?? '';
$empresa = $_GET['empresa'] ?? '';
$linha = $_GET['linha'] ?? '';
$modelo = $_GET['modelo'] ?? '';

// Consulta para obter os registros do banco com filtros
$sql = "SELECT 
    f.id, 
    f.data_lancamento,
    f.cod_gestor,
    f.mes_referencia,
    f.empresa,
    i.LINHA AS linha_produto,
    f.modelo_produto,
    f.quantidade
FROM forecast_entries f
LEFT JOIN (
    SELECT DISTINCT MODELO, LINHA FROM V_DEPARA_ITEM
) i ON f.modelo_produto = i.MODELO
WHERE 1=1";

$params = [];

// Aplicar filtros conforme os valores preenchidos
if (!empty($mesReferencia)) {
    $sql .= " AND f.mes_referencia = ?";
    $params[] = $mesReferencia;
}

if (!empty($gestor)) {
    $sql .= " AND f.cod_gestor = ?";
    $params[] = $gestor;
}

if (!empty($empresa)) {
    $sql .= " AND f.empresa = ?";
    $params[] = $empresa;
}

if (!empty($linha)) {
    $sql .= " AND i.LINHA = ?";
    $params[] = $linha;
}

if (!empty($modelo)) {
    $sql .= " AND f.modelo_produto = ?";
    $params[] = $modelo;
}

// Ordenar por mes_referencia, cod_gestor, empresa, linha, modelo
$sql .= " ORDER BY f.mes_referencia DESC, f.cod_gestor, f.empresa, i.LINHA, f.modelo_produto";

$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die("<div class='alert alert-danger'>Erro ao carregar histórico de apontamentos.</div>");
}

// Inicializar arrays para os filtros
$opcoesMeses = [];
$opcoesGestores = [];
$opcoesEmpresas = [];
$opcoesLinhas = [];
$opcoesModelos = [];

$resultados = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $resultados[] = $row;

    // Coletar opções exclusivas para os filtros
    if (!in_array($row['mes_referencia'], $opcoesMeses)) {
        $opcoesMeses[] = $row['mes_referencia'];
    }
    if (!in_array($row['cod_gestor'], $opcoesGestores) && $row['cod_gestor'] !== null) {
        $opcoesGestores[] = $row['cod_gestor'];
    }
    if (!in_array($row['empresa'], $opcoesEmpresas)) {
        $opcoesEmpresas[] = $row['empresa'];
    }
    if (!in_array($row['linha_produto'], $opcoesLinhas) && $row['linha_produto'] !== null) {
        $opcoesLinhas[] = $row['linha_produto'];
    }
    if (!in_array($row['modelo_produto'], $opcoesModelos)) {
        $opcoesModelos[] = $row['modelo_produto'];
    }
}
?>

<div class="content">
    <h2 class="mb-4"><i class="bi bi-clock-history"></i> Histórico de Apontamentos</h2>

    <!-- Filtros -->
    <div class="card shadow-sm p-4 mb-4">
        <form method="GET" action="index.php" id="filterForm">
            <input type="hidden" name="page" value="historico_forecast">
            <div class="row g-3">
                <!-- Filtro Mês de Referência -->
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

                <!-- Filtro Gestor -->
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

                <!-- Filtro Empresa -->
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

                <!-- Filtro Linha -->
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

                <!-- Filtro Modelo -->
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
                <a href="index.php?page=historico_forecast" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Limpar Filtros
                </a>
            </div>
        </form>
    </div>

    <!-- Tabela -->
    <div class="card shadow-sm p-4">
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Data de Lançamento</th>
                    <th>Código do Gestor</th>
                    <th>Mês de Referência</th>
                    <th>Empresa</th>
                    <th>Linha</th>
                    <th>Modelo</th>
                    <th>Quantidade</th>
                    <th>Novo Valor</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resultados as $row): ?>
                    <tr>
                        <td><?= $row['data_lancamento'] ? $row['data_lancamento']->format('d/m/Y') : 'N/A'; ?></td>
                        <td><?= htmlspecialchars($row['cod_gestor'] ?? 'N/A'); ?></td>
                        <td><?= htmlspecialchars($row['mes_referencia']); ?></td>
                        <td><?= htmlspecialchars($row['empresa']); ?></td>
                        <td><?= htmlspecialchars($row['linha_produto'] ?? 'N/A'); ?></td>
                        <td><?= htmlspecialchars($row['modelo_produto']); ?></td>
                        <td><?= number_format($row['quantidade'], 0, ',', '.'); ?></td>
                        <td>
                            <input type="number" class="form-control form-control-sm update-value" id="update_<?= $row['id']; ?>" min="0">
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary update-button" data-id="<?= $row['id']; ?>">
                                <i class="bi bi-pencil"></i> Alterar
                            </button>
                        </td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".update-button").forEach(button => {
        button.addEventListener("click", function () {
            let id = this.getAttribute("data-id");
            let inputField = document.getElementById("update_" + id);
            let newValue = inputField.value.trim();

            if (newValue === "") {
                alert("Por favor, insira um valor para atualizar.");
                return;
            }

            fetch("index.php?page=update_forecast", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id=${id}&novo_valor=${newValue}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Quantidade atualizada com sucesso!");
                    location.reload();
                } else {
                    alert("Erro ao atualizar: " + data.message);
                }
            })
            .catch(error => {
                console.error("Erro:", error);
                alert("Erro ao conectar ao servidor.");
            });
        });
    });
});
</script>

<?php include __DIR__ . '/../templates/footer.php'; ?>
