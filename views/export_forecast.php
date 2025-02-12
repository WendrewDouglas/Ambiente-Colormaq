<?php
// Não deve haver nenhum espaço ou saída antes deste <?php
ob_start(); // Inicia o buffer de saída

require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Carrega a biblioteca PHPSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Cria a conexão com o banco
$db = new Database();
$conn = $db->getConnection();

// Configurações de memória e tempo
ini_set('memory_limit', '512M');
set_time_limit(300);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Captura os filtros enviados por GET
$mesReferencia = $_GET['mesReferencia'] ?? '';
$gestor       = $_GET['gestor'] ?? '';
$empresa      = $_GET['empresa'] ?? '';
$linha        = $_GET['linha'] ?? '';
$modelo       = $_GET['modelo'] ?? '';

// Construir a consulta SQL com filtros dinâmicos
$sql = "SELECT 
            FORMAT(f.mes_referencia, 'MMMM/yyyy', 'pt-BR') AS mes_referencia, 
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

// Executa a consulta
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    ob_end_clean();
    die("Erro ao carregar os dados: " . print_r(sqlsrv_errors(), true));
}

// Em vez de usar sqlsrv_has_rows (que pode não funcionar conforme esperado), acumulamos os dados em um array.
$rows = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $rows[] = $row;
}

if (empty($rows)) {
    ob_end_clean();
    die("Nenhum dado encontrado para exportação.");
}

// Cria uma nova planilha do Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Define os cabeçalhos da planilha
$headers = ["Mês Referência", "Gestor", "Empresa", "SKU", "Linha", "Modelo", "Descrição", "Quantidade", "Status"];
$sheet->fromArray([$headers], NULL, 'A1');

// Preenche os dados na planilha
$rowIndex = 2;
foreach ($rows as $row) {
    // Processa o campo mes_referencia: se for uma string, converte para o formato desejado
    $dataMes = $row['mes_referencia'];
    if ($dataMes instanceof DateTime) {
        $dataMes = $dataMes->format('F/Y');
    } elseif (is_string($dataMes)) {
        $dataMes = date('F/Y', strtotime($dataMes));
    }
    $data = [
        $dataMes,
        $row['gestor'],
        $row['empresa'],
        $row['cod_produto'],
        $row['LINHA'],
        $row['MODELO'],
        $row['DESCITEM'],
        is_numeric($row['quantidade']) ? number_format($row['quantidade'], 0, ',', '.') : $row['quantidade'],
        $row['STATUS']
    ];
    $sheet->fromArray([$data], NULL, "A{$rowIndex}");
    $rowIndex++;
}

// Cria um arquivo temporário para salvar o Excel
$temp_file = tempnam(sys_get_temp_dir(), 'forecast_export_') . '.xlsx';
$writer = new Xlsx($spreadsheet);
$writer->save($temp_file);

// Limpa qualquer conteúdo do buffer para garantir que os cabeçalhos sejam enviados corretamente
if (ob_get_length()) {
    ob_end_clean();
}

// Define os cabeçalhos para o download do arquivo Excel
header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="forecast_export.xlsx"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($temp_file));

// Envia o arquivo para o navegador e depois exclui o arquivo temporário
readfile($temp_file);
unlink($temp_file);
exit;
