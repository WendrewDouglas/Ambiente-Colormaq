<?php
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Carrega a biblioteca PHPSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Criar conexão com o banco
$db = new Database();
$conn = $db->getConnection();

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
    die("Erro ao carregar os dados: " . print_r(sqlsrv_errors(), true));
}

// Criar nova planilha do Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Definir cabeçalhos
$headers = ["Mês Referência", "Gestor", "Empresa", "SKU", "Linha", "Modelo", "Descrição", "Quantidade", "Status"];
$sheet->fromArray([$headers], NULL, 'A1');

// Preencher os dados na planilha
$rowIndex = 2;
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $data = [
        $row['mes_referencia'],
        $row['gestor'],
        $row['empresa'],
        $row['cod_produto'],
        $row['LINHA'],
        $row['MODELO'],
        $row['DESCITEM'],
        $row['quantidade'],
        $row['STATUS']
    ];
    $sheet->fromArray([$data], NULL, "A$rowIndex");
    $rowIndex++;
}

// Criar arquivo Excel para download
$filename = "forecast_export.xlsx";
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=$filename");
header("Cache-Control: max-age=0");

// Criar o arquivo e enviá-lo para o navegador
$writer = new Xlsx($spreadsheet);
$writer->save("php://output");

error_reporting(E_ALL);
ini_set('display_errors', 1);

exit;
