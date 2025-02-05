<?php
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Carrega a biblioteca PHPSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Criar conexão com o banco
$db = new Database();
$conn = $db->getConnection();

ini_set('memory_limit', '512M');
set_time_limit(300);

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Capturar filtros do formulário
$mesReferencia = $_GET['mesReferencia'] ?? '';
$gestor = $_GET['gestor'] ?? '';
$empresa = $_GET['empresa'] ?? '';
$linha = $_GET['linha'] ?? '';
$modelo = $_GET['modelo'] ?? '';

// Construção da consulta SQL com filtros dinâmicos
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

// Se houver condições, adicionamos WHERE dinamicamente
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Ordenação do mais recente para o mais antigo
$sql .= " ORDER BY f.mes_referencia DESC";

$stmt = sqlsrv_query($conn, $sql, $params);


if ($stmt === false) {
    die("<div style='color: red;'>Erro ao carregar os dados: " . print_r(sqlsrv_errors(), true) . "</div>");
}

// Verificar se há dados para exportação
if ($stmt === false || sqlsrv_has_rows($stmt) === false) {
    die("<div style='color: orange;'>Nenhum dado encontrado para exportação.</div>");
}

// Criar nova planilha do Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

//verifica se existe saída da página antes do header
if (headers_sent()) {
    die("<div style='color: red;'>Erro: Cabeçalhos já foram enviados.</div>");
}


// Definir cabeçalhos
$headers = ["Mês Referência", "Gestor", "Empresa", "SKU", "Linha", "Modelo", "Descrição", "Quantidade", "Status"];
$sheet->fromArray([$headers], NULL, 'A1');

// Preencher os dados na planilha
$rowIndex = 2;
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // Capturar e converter mes_referencia corretamente
    $dataMes = $row['mes_referencia'];
    if ($dataMes instanceof DateTime) {
        $dataMes = $dataMes->format('F/Y');
    } elseif (is_string($dataMes)) {
        $dataMes = date('F/Y', strtotime($dataMes)); // Converte string para formato esperado
    }

    // Criar array de dados para a planilha
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

    // Adicionar os dados à planilha
    $sheet->fromArray([$data], NULL, "A$rowIndex");
    $rowIndex++;
}

// Criar e salvar o arquivo Excel localmente
$filepath = __DIR__ . "/forecast_export.xlsx";
$writer = new Xlsx($spreadsheet);
$writer->save($filepath);

// Enviar o arquivo para o navegador
if (file_exists($filepath)) {
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=forecast_export.xlsx");
    header("Content-Length: " . filesize($filepath));
    readfile($filepath);
    unlink($filepath); // Excluir o arquivo após o download
    exit;
} else {
    die("Erro ao gerar o arquivo Excel.");
}
