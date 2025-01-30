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

// Buscar lançamentos no banco de dados
$sql = "SELECT 
            data_lancamento, 
            modelo_produto, 
            mes_referencia, 
            empresa, 
            quantidade
        FROM forecast_entries
        ORDER BY data_lancamento DESC";
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
                    <th>Data de Lançamento</th>
                    <th>Modelo</th>
                    <th>Mês de Referência</th>
                    <th>Empresa</th>
                    <th>Quantidade</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['data_lancamento']->format('d/m/Y')); ?></td>
                        <td><?= htmlspecialchars($row['modelo_produto']); ?></td>
                        <td><?= htmlspecialchars($row['mes_referencia']); ?></td>
                        <td><?= htmlspecialchars($row['empresa']); ?></td>
                        <td><?= number_format($row['quantidade'], 0, ',', '.'); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
