<?php
require_once __DIR__ . '/../includes/auto_check.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/permissions.php';

// Permitir apenas ADMIN e GESTOR acessar
verificarPermissao('consulta_lancamentos');

// Configuração da página
$pageTitle = 'Consulta de Lançamentos - Forecast System';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';

// Criar conexão com o banco
$db = new Database();
$conn = $db->getConnection();

// Forçar o locale para exibir meses em português
setlocale(LC_TIME, 'ptb.UTF-8', 'ptb', 'portuguese', 'portuguese_brazil');

// Buscar lançamentos no banco de dados
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

if ($stmt === false) {
    die("<div class='alert alert-danger'>Erro ao carregar os lançamentos.</div>");
}
?>

<div class="content">
    <h2 class="mb-4"><i class="bi bi-search"></i> Consulta de Lançamentos</h2>

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
                        <td><?= htmlspecialchars($row['mes_referencia']->format('m/Y')); ?></td>
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

<?php include __DIR__ . '/../templates/footer.php'; ?>
