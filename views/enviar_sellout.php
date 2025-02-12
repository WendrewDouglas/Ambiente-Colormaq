<?php
require_once __DIR__ . '/../includes/auto_check.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

verificarPermissao('enviar_sellout');

$pageTitle = 'Enviar Sell-Out - Forecast System';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';

$db = new Database();
$conn = $db->getConnection();

$errorMessage = "";
$successMessage = "";

// Função para capturar o IP do usuário
function getUserIP() {
    return $_SERVER['REMOTE_ADDR'] ?? 'Desconhecido';
}

// Função para converter data do formato DD/MM/YYYY para YYYY-MM-DD ou converter serial do Excel
function formatDate($dateString) {
    $dateString = trim($dateString);

    // Se a data estiver no formato DD/MM/YYYY
    $dateObj = DateTime::createFromFormat('d/m/Y', $dateString);
    if ($dateObj && $dateObj->format('d/m/Y') === $dateString) {
        return $dateObj->format('Y-m-d');
    }

    // Se a data for um número serial do Excel
    if (is_numeric($dateString) && $dateString > 40000) {
        $excelBaseDate = DateTime::createFromFormat('Y-m-d', '1899-12-30');
        $excelBaseDate->modify("+{$dateString} days");
        return $excelBaseDate->format('Y-m-d');
    }

    return null;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = "Erro no upload do arquivo.";
    } else {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'xlsx') {
            $errorMessage = "Formato de arquivo inválido. Apenas arquivos .xlsx são permitidos.";
        } else {
            try {
                $spreadsheet = IOFactory::load($file['tmp_name']);
                $sheet = $spreadsheet->getActiveSheet();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $headerRow = $sheet->rangeToArray("A1:" . $highestColumn . "1", NULL, TRUE, FALSE);
                $headers = array_map('trim', $headerRow[0]);

                // Nova estrutura das colunas esperadas
                $expectedColumns = [
                    "Data",
                    "Cód. Cliente",
                    "Varejo",
                    "Bandeira",
                    "Filial/CD",
                    "Cód. Prod. Colormaq",
                    "Tipo Venda",
                    "Qtde Venda",
                    "Qtde Estoque",
                    "Vlr Venda",
                    "Cidade",
                    "UF"
                ];

                // Colunas obrigatórias
                $requiredColumns = [
                    "Data",
                    "Cód. Cliente",
                    "Cód. Prod. Colormaq",
                    "Qtde Venda",
                    "Qtde Estoque",
                    "Varejo"
                ];

                $expectedLower = array_map('strtolower', $expectedColumns);
                $headersLower = array_map('strtolower', $headers);

                if ($headersLower !== $expectedLower) {
                    $errorMessage = "Arquivo fora do padrão. Verifique as colunas obrigatórias e tente novamente.";
                } else {
                    $inserted = 0;
                    $insertData = [];
                    $usuarioLogado = $_SESSION['user_name'] ?? 'Não identificado';
                    $dataEnvio = date('Y-m-d H:i:s');
                    $ipEnvio = getUserIP();

                    for ($row = 2; $row <= $highestRow; $row++) {
                        $rowData = $sheet->rangeToArray("A{$row}:" . $highestColumn . $row, NULL, TRUE, FALSE);
                        $data = $rowData[0];

                        if (count($data) < count($expectedColumns)) {
                            $errorMessage = "Erro na linha $row: Dados incompletos.";
                            break;
                        }

                        // Verificar se as colunas obrigatórias estão preenchidas
                        foreach ($requiredColumns as $col) {
                            $index = array_search(strtolower($col), $headersLower);
                            if ($index !== false && (empty($data[$index]) || trim($data[$index]) === '')) {
                                $errorMessage = "Erro na linha $row: a coluna '{$col}' é obrigatória e não pode estar vazia.";
                                break 2;
                            }
                        }

                        $dataReferencia = formatDate($data[0]); // Converte a data para o formato correto

                        if (!$dataReferencia) {
                            $errorMessage = "Erro na linha $row: a coluna 'Data' deve estar no formato DD/MM/YYYY.";
                            break;
                        }

                        $insertData[] = [
                            $dataReferencia, // Data
                            trim($data[1]),  // Cód. Cliente
                            trim($data[2]),  // Varejo
                            trim($data[3]),  // Bandeira
                            trim($data[4]),  // Filial/CD
                            trim($data[5]),  // Cód. Prod. Colormaq
                            trim($data[6]),  // Tipo Venda
                            is_numeric($data[7]) ? (int)$data[7] : 0,  // Qtde Venda
                            is_numeric($data[8]) ? (int)$data[8] : 0,  // Qtde Estoque
                            is_numeric($data[9]) ? (float)$data[9] : 0.0,  // Vlr Venda
                            trim($data[10]), // Cidade
                            trim($data[11]), // UF
                            $usuarioLogado,
                            $dataEnvio,
                            $ipEnvio
                        ];
                    }

                    if (!empty($insertData)) {
                        $sqlInsert = "INSERT INTO SellOutColor 
                            (data_referencia, cod_cliente, varejo, bandeira, filial_cd, cod_prod_colormaq, tipo_venda, qtde_venda, qtde_estoque, vlr_venda, cidade, uf, user_import, data_envio, ip_envio) 
                            VALUES ";
                        $values = [];
                        $params = [];
                        foreach ($insertData as $row) {
                            $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            array_push($params, ...$row);
                        }
                        $sqlInsert .= implode(",", $values);

                        $stmtInsert = sqlsrv_query($conn, $sqlInsert, $params);
                        if ($stmtInsert === false) {
                            $errorMessage = "Erro ao inserir registros: " . print_r(sqlsrv_errors(), true);
                        } else {
                            $successMessage = "Arquivo importado com sucesso. Registros inseridos: " . count($insertData);
                        }
                    }
                }
            } catch (Exception $e) {
                $errorMessage = "Erro ao ler o arquivo Excel: " . $e->getMessage();
            }
        }
    }
}


$filterCliente = $_GET['cod_cliente'] ?? '';
$filterDataInicio = $_GET['data_inicio'] ?? '';
$filterDataFim = $_GET['data_fim'] ?? '';

$pagina = $_GET['pagina'] ?? 1;
$limite = 100;
$offset = ($pagina - 1) * $limite;

// Construção da query com filtros
$where = [];
$params = [];

if (!empty($filterCliente)) {
    $where[] = "cod_cliente = ?";
    $params[] = $filterCliente;
}

if (!empty($filterDataInicio) && !empty($filterDataFim)) {
    $where[] = "data_referencia BETWEEN ? AND ?";
    $params[] = $filterDataInicio;
    $params[] = $filterDataFim;
}

$whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
$query = "SELECT * FROM SellOutColor $whereClause ORDER BY data_referencia DESC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
$params[] = $offset;
$params[] = $limite;

$stmt = sqlsrv_query($conn, $query, $params);
$dataRows = [];

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $dataRows[] = $row;
    }
}

// Contagem total para paginação
$queryCount = "SELECT COUNT(*) AS total FROM SellOutColor $whereClause";
$stmtCount = sqlsrv_query($conn, $queryCount, array_slice($params, 0, -2)); // Remove os LIMIT do count
$totalRegistros = ($stmtCount && $row = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC)) ? $row['total'] : 0;
$totalPaginas = ceil($totalRegistros / $limite);
?>


<div class="content">
    <h2 class="mb-4"><i class="bi bi-upload"></i> Enviar Sell-Out</h2>

    <div class="card shadow-sm p-4 mb-4">
        <h4>📌 Como Enviar o Arquivo Sell-Out</h4>
        <p>
            Para garantir que o seu arquivo seja processado corretamente, siga as instruções abaixo:
        </p>
        <ul class="list-group list-group-flush mb-3">
            <li class="list-group-item">
                O arquivo deve estar no formato <strong>Excel (.xlsx)</strong>.
            </li>
            <li class="list-group-item">
                O arquivo deve conter as seguintes colunas na ordem correta:
                <code>Data, Cód. Cliente, Varejo, Bandeira, Filial/CD, Cód. Prod. Colormaq, Tipo Venda, Qtde Venda, Qtde Estoque, Vlr Venda, Cidade, UF</code>.
            </li>
            <li class="list-group-item">
                A coluna <strong>Cód. Cliente</strong> é obrigatória e deve ser preenchida corretamente.
            </li>
            <li class="list-group-item">
                A coluna <strong>Data</strong> deve estar no formato <code>DD/MM/YYYY</code> (exemplo: <code>01/01/2025</code>).
            </li>
            <li class="list-group-item">
                Os valores de <strong>Qtde Venda</strong> e <strong>Qtde Estoque</strong> devem ser números inteiros.
            </li>
            <li class="list-group-item">
                O valor de <strong>Vlr Venda</strong> deve estar no formato decimal (exemplo: <code>1999,99</code>).
            </li>
            <li class="list-group-item">
                Não altere os nomes das colunas, pois o sistema identifica os dados por elas.
            </li>
        </ul>

        <p>
            📥 <a href="../documents/modelo_sellout.xlsx" class="btn btn-outline-primary btn-sm" download>
                <i class="bi bi-file-earmark-excel"></i> Baixar Planilha Modelo
            </a>
        </p>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>
    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm p-4 mb-4">
        <h4><i class="bi bi-upload"></i> Enviar Arquivo</h4>
        <p>Selecione o arquivo Excel (.xlsx) com os dados formatados corretamente e clique no botão "Enviar Arquivo".</p>

        <form method="POST" action="index.php?page=enviar_sellout" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="excel_file" class="form-label">Escolha um arquivo</label>
                <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-upload"></i> Enviar Arquivo
            </button>
        </form>
    </div>
</div>
<div class="content">
    <h2 class="mb-4"><i class="bi bi-table"></i> Visualizar Sell-Out</h2>

    <div class="card shadow-sm p-4 mb-4">
        <h4>🔎 Filtros</h4>
        <form method="GET">
            <div class="row">
                <div class="col-md-4">
                    <label for="cod_cliente" class="form-label">Cód. Cliente</label>
                    <input type="text" class="form-control" id="cod_cliente" name="cod_cliente" value="<?= htmlspecialchars($filterCliente) ?>">
                </div>
                <div class="col-md-3">
                    <label for="data_inicio" class="form-label">Data Início</label>
                    <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?= htmlspecialchars($filterDataInicio) ?>">
                </div>
                <div class="col-md-3">
                    <label for="data_fim" class="form-label">Data Fim</label>
                    <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?= htmlspecialchars($filterDataFim) ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filtrar</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card shadow-sm p-4">
        <h4>📊 Registros Sell-Out</h4>

        <form id="delete-form" method="POST" action="../auth/deletar_registros_sellout.php">
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th><input type="checkbox" id="select-all"></th>
                        <th>Data</th>
                        <th>Cód. Cliente</th>
                        <th>Varejo</th>
                        <th>Bandeira</th>
                        <th>Filial/CD</th>
                        <th>Cód. Prod. Colormaq</th>
                        <th>Tipo Venda</th>
                        <th>Qtde Venda</th>
                        <th>Qtde Estoque</th>
                        <th>Vlr Venda</th>
                        <th>Cidade</th>
                        <th>UF</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataRows as $row): ?>
                        <tr>
                            <td><input type="checkbox" name="delete_ids[]" value="<?= $row['id'] ?>"></td>
                            <td><?= htmlspecialchars($row['data_referencia']->format('Y-m-d')) ?></td>
                            <td><?= htmlspecialchars($row['cod_cliente']) ?></td>
                            <td><?= htmlspecialchars($row['varejo']) ?></td>
                            <td><?= htmlspecialchars($row['bandeira']) ?></td>
                            <td><?= htmlspecialchars($row['filial_cd']) ?></td>
                            <td><?= htmlspecialchars($row['cod_prod_colormaq']) ?></td>
                            <td><?= htmlspecialchars($row['tipo_venda']) ?></td>
                            <td><?= htmlspecialchars($row['qtde_venda']) ?></td>
                            <td><?= htmlspecialchars($row['qtde_estoque']) ?></td>
                            <td><?= htmlspecialchars($row['vlr_venda']) ?></td>
                            <td><?= htmlspecialchars($row['cidade']) ?></td>
                            <td><?= htmlspecialchars($row['uf']) ?></td>
                            <td>
                                <button type="submit" name="delete_id" value="<?= $row['id'] ?>" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i> Excluir
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <button type="submit" class="btn btn-danger mt-3"><i class="bi bi-trash"></i> Excluir Selecionados</button>
        </form>

        <!-- Paginação -->
        <nav class="mt-3">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                    <li class="page-item <?= ($pagina == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $i ?>&cod_cliente=<?= htmlspecialchars($filterCliente) ?>&data_inicio=<?= htmlspecialchars($filterDataInicio) ?>&data_fim=<?= htmlspecialchars($filterDataFim) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
</div>

<script>
document.getElementById("select-all").addEventListener("change", function() {
    let checkboxes = document.querySelectorAll("input[name='delete_ids[]']");
    checkboxes.forEach(checkbox => checkbox.checked = this.checked);
});
</script>
