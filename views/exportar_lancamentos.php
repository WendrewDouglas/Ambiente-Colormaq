<?php
require_once __DIR__ . '/../includes/db_connection.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=forecast_lancamentos.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Criar conexão com o banco
$db = new Database();
$conn = $db->getConnection();

// Query para obter os dados
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
    LEFT JOIN (
        SELECT DISTINCT CODITEM, DESCITEM, LINHA, MODELO, STATUS FROM V_DEPARA_ITEM
    ) i ON f.cod_produto = i.CODITEM
    WHERE 1=1
    ORDER BY mes_referencia DESC";

$stmt = sqlsrv_query($conn, $sql);

// Início da tabela
echo "<table border='1'>";
echo "<tr>
        <th>Mês Referência</th>
        <th>Gestor</th>
        <th>Empresa</th>
        <th>SKU</th>
        <th>Linha</th>
        <th>Modelo</th>
        <th>Descrição</th>
        <th>Quantidade</th>
        <th>Status</th>
      </tr>";

// Preenchimento da tabela
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "<tr>
            <td>{$row['mes_referencia']->format('m/Y')}</td>
            <td>{$row['gestor']}</td>
            <td>{$row['empresa']}</td>
            <td>{$row['cod_produto']}</td>
            <td>{$row['LINHA']}</td>
            <td>{$row['MODELO']}</td>
            <td>{$row['DESCITEM']}</td>
            <td>" . number_format($row['quantidade'], 0, ',', '.') . "</td>
            <td>{$row['STATUS']}</td>
          </tr>";
}

echo "</table>";
?>
