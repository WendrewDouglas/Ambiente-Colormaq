<?php
// NÃO inclua espaços ou linhas em branco antes deste <?php
require_once __DIR__ . '/../includes/auto_check.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../vendor/autoload.php';


// Verifica a permissão (crie ou utilize uma permissão adequada, ex.: 'enviar_sellout')
verificarPermissao('enviar_sellout');

$pageTitle = 'Enviar Sell-Out - Forecast System';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';

$db = new Database();
$conn = $db->getConnection();

require_once __DIR__ . '/../vendor/autoload.php'; // PHPSpreadsheet autoload
use PhpOffice\PhpSpreadsheet\IOFactory;

$errorMessage = "";
$successMessage = "";

// Processamento do arquivo quando o formulário for submetido
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = "Erro no upload do arquivo.";
    } else {
        // Verificar a extensão do arquivo
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($ext) !== 'xlsx') {
            $errorMessage = "Formato de arquivo inválido. Apenas arquivos .xlsx são permitidos.";
        } else {
            try {
                $spreadsheet = IOFactory::load($file['tmp_name']);
                $sheet = $spreadsheet->getActiveSheet();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Ler a primeira linha (cabeçalho)
                $headerRow = $sheet->rangeToArray("A1:" . $highestColumn . "1", NULL, TRUE, FALSE);
                $headers = array_map('trim', $headerRow[0]);

                // Definir as colunas esperadas
                $expectedColumns = [
                    "Código Martins",
                    "Mercadoria",
                    "Filial",
                    "Qde Estoque Disp.(*",
                    "Média Mensal Venda",
                    "QdeVenda",
                    "Cobertura",
                    "Qde SaldoPedido",
                    "Lost Sales"
                ];
                // Para uma comparação case-insensitive e sem espaços extras:
                $expectedLower = array_map('strtolower', $expectedColumns);
                $headersLower = array_map('strtolower', $headers);

                // Supondo que já temos a variável $usuarioLogado definida:
                $usuarioLogado = $_SESSION['user_name'] ?? 'Não identificado';


                if ($headersLower !== $expectedLower) {
                    $errorMessage = "Arquivo fora do padrão. Não foi possível subir os dados. Por favor, corrija o arquivo e, em caso de dúvida, entre em contato com TI Colormaq.";
                } else {
                    // Processar linhas a partir da segunda
                    $inserted = 0;
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $rowData = $sheet->rangeToArray("A{$row}:" . $highestColumn . $row, NULL, TRUE, FALSE);
                        $data = $rowData[0];
                    
                        // Prepara a query de INSERT, incluindo a coluna user_import
                        $sqlInsert = "INSERT INTO SellOut 
                            (codigo_martins, mercadoria, filial, qde_estoque_disp, media_mensal_venda, qde_venda, cobertura, qde_saldo_pedido, lost_sales, user_import)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $paramsInsert = [
                            $data[0],
                            $data[1],
                            $data[2],
                            is_numeric($data[3]) ? $data[3] : 0,
                            is_numeric($data[4]) ? $data[4] : 0,
                            is_numeric($data[5]) ? $data[5] : 0,
                            is_numeric($data[6]) ? $data[6] : 0,
                            is_numeric($data[7]) ? $data[7] : 0,
                            is_numeric($data[8]) ? $data[8] : 0,
                            $usuarioLogado
                        ];
                        $stmtInsert = sqlsrv_query($conn, $sqlInsert, $paramsInsert);
                        if ($stmtInsert === false) {
                            $errorMessage = "Erro ao inserir dados na linha $row: " . print_r(sqlsrv_errors(), true);
                            break;
                        }
                    }
                    if (empty($errorMessage)) {
                        $successMessage = "Arquivo importado com sucesso. Registros inseridos: $inserted.";
                    }
                }
            } catch (Exception $e) {
                $errorMessage = "Erro ao ler o arquivo Excel: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="content">
    <h2 class="mb-4"><i class="bi bi-upload"></i> Enviar Sell-Out</h2>
    
    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>
    <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <!-- Formulário de Upload -->
    <div class="card shadow-sm p-4 mb-4">
        <form method="POST" action="index.php?page=enviar_sellout" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="excel_file" class="form-label">Selecione o arquivo Excel (.xlsx)</label>
                <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-upload"></i> Enviar Arquivo
            </button>
        </form>
    </div>

    <!-- Filtros para Visualização dos Registros -->
    <?php
    $filtroCodigo = $_GET['codigo_martins'] ?? '';
    $filtroMercadoria = $_GET['mercadoria'] ?? '';
    $filtroFilial = $_GET['filial'] ?? '';

    $sqlView = "SELECT * FROM SellOut WHERE 1=1";
    $viewParams = [];
    if (!empty($filtroCodigo)) {
        $sqlView .= " AND codigo_martins = ?";
        $viewParams[] = $filtroCodigo;
    }
    if (!empty($filtroMercadoria)) {
        $sqlView .= " AND mercadoria = ?";
        $viewParams[] = $filtroMercadoria;
    }
    if (!empty($filtroFilial)) {
        $sqlView .= " AND filial = ?";
        $viewParams[] = $filtroFilial;
    }
    $sqlView .= " ORDER BY data_importacao DESC";
    $stmtView = sqlsrv_query($conn, $sqlView, $viewParams);
    ?>
    <div class="card shadow-sm p-4 mb-4">
        <h3>Visualizar Sell-Out</h3>
        <form method="GET" action="index.php" id="filterSellOutForm">
            <input type="hidden" name="page" value="enviar_sellout">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="codigo_martins" class="form-label">Código Martins:</label>
                    <input type="text" class="form-control" id="codigo_martins" name="codigo_martins" value="<?= htmlspecialchars($filtroCodigo) ?>">
                </div>
                <div class="col-md-4">
                    <label for="mercadoria" class="form-label">Mercadoria:</label>
                    <input type="text" class="form-control" id="mercadoria" name="mercadoria" value="<?= htmlspecialchars($filtroMercadoria) ?>">
                </div>
                <div class="col-md-4">
                    <label for="filial" class="form-label">Filial:</label>
                    <input type="text" class="form-control" id="filial" name="filial" value="<?= htmlspecialchars($filtroFilial) ?>">
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-filter"></i> Aplicar Filtros
                </button>
                <a href="index.php?page=enviar_sellout" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Limpar Filtros
                </a>
                <a href="export_sellout.php?codigo_martins=<?= urlencode($filtroCodigo) ?>&mercadoria=<?= urlencode($filtroMercadoria) ?>&filial=<?= urlencode($filtroFilial) ?>" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel"></i> Exportar para Excel
                </a>
            </div>
        </form>
    </div>

    <!-- Tabela de Visualização dos Dados -->
    <div class="card shadow-sm p-4">
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Código Martins</th>
                    <th>Mercadoria</th>
                    <th>Filial</th>
                    <th>Qde Estoque Disp</th>
                    <th>Média Mensal Venda</th>
                    <th>QdeVenda</th>
                    <th>Cobertura</th>
                    <th>Qde SaldoPedido</th>
                    <th>Lost Sales</th>
                    <th>Data Importação</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = sqlsrv_fetch_array($stmtView, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id']) ?></td>
                        <td><?= htmlspecialchars($row['codigo_martins']) ?></td>
                        <td><?= htmlspecialchars($row['mercadoria']) ?></td>
                        <td><?= htmlspecialchars($row['filial']) ?></td>
                        <td><?= number_format($row['qde_estoque_disp'], 2, ',', '.') ?></td>
                        <td><?= number_format($row['media_mensal_venda'], 2, ',', '.') ?></td>
                        <td><?= number_format($row['qde_venda'], 2, ',', '.') ?></td>
                        <td><?= number_format($row['cobertura'], 2, ',', '.') ?></td>
                        <td><?= number_format($row['qde_saldo_pedido'], 2, ',', '.') ?></td>
                        <td><?= number_format($row['lost_sales'], 2, ',', '.') ?></td>
                        <td><?= date_format($row['data_importacao'], 'd/m/Y H:i:s') ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
