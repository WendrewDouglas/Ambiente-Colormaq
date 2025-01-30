<?php
require_once __DIR__ . '/../includes/auto_check.php';
require_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../includes/permissions.php';

// Permitir apenas ADMIN acessar
verificarPermissao('depara_comercial');

// Configuração da página
$pageTitle = 'Gestores Comerciais - Forecast System';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';

// Criar conexão com o banco
$db = new Database();
$conn = $db->getConnection();
if (!$conn) {
    die("<div class='alert alert-danger'>Erro de conexão com o banco: " . print_r(sqlsrv_errors(), true) . "</div>");
}

$sqlTest = "SELECT TOP 1 * FROM DW..DEPARA_COMERCIAL";
$stmtTest = sqlsrv_query($conn, $sqlTest);

if ($stmtTest === false) {
    die("<div class='alert alert-danger'>Erro ao acessar a tabela: " . print_r(sqlsrv_errors(), true) . "</div>");
}


// Consulta para obter os usuários cadastrados no sistema
$sqlUsuarios = "SELECT DISTINCT name FROM users ORDER BY name";
$stmtUsuarios = sqlsrv_query($conn, $sqlUsuarios);

$usuarios = [];
while ($rowUsuario = sqlsrv_fetch_array($stmtUsuarios, SQLSRV_FETCH_ASSOC)) {
    $usuarios[] = $rowUsuario['name'];
}

// Consulta para obter os registros da tabela
$sql = "SELECT 
            Regional, 
            GNV, 
            NomeRegional, 
            Analista 
        FROM DW..DEPARA_COMERCIAL
        WHERE Regional IS NOT NULL AND LTRIM(RTRIM(Regional)) <> ''
        ORDER BY Regional";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die("<div class='alert alert-danger'>Erro ao carregar os gestores comerciais.</div>");
}
?>

<div class="content">
    <h2 class="mb-4"><i class="bi bi-person-badge"></i> Gestores Comerciais</h2>

    <div class="card shadow-sm p-4">
        <p class="text-muted">A tabela abaixo lista os gestores comerciais cadastrados no sistema.</p>

        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Código do Gestor</th>
                    <th>GNV</th>
                    <th>Regional</th>
                    <th>Analista</th>
                    <th>Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['Regional']); ?></td>
                        
                        <!-- GNV -->
                        <td>
                            <select class="form-select form-select-sm update-field" 
                                data-id="<?= $row['Regional']; ?>" 
                                data-column="GNV"
                                data-current-value="<?= htmlspecialchars($row['GNV']); ?>">
                                <option value="<?= htmlspecialchars($row['GNV']); ?>"><?= htmlspecialchars($row['GNV']); ?> (Atual)</option>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option value="<?= htmlspecialchars($usuario); ?>"><?= htmlspecialchars($usuario); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>

                        <!-- NomeRegional -->
                        <td>
                            <select class="form-select form-select-sm update-field" 
                                data-id="<?= $row['Regional']; ?>" 
                                data-column="NomeRegional"
                                data-current-value="<?= htmlspecialchars($row['NomeRegional']); ?>">
                                <option value="<?= htmlspecialchars($row['NomeRegional']); ?>"><?= htmlspecialchars($row['NomeRegional']); ?> (Atual)</option>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option value="<?= htmlspecialchars($usuario); ?>"><?= htmlspecialchars($usuario); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>

                        <!-- Analista -->
                        <td>
                            <select class="form-select form-select-sm update-field" 
                                data-id="<?= $row['Regional']; ?>" 
                                data-column="Analista"
                                data-current-value="<?= htmlspecialchars($row['Analista']); ?>">
                                <option value="<?= htmlspecialchars($row['Analista']); ?>"><?= htmlspecialchars($row['Analista']); ?> (Atual)</option>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option value="<?= htmlspecialchars($usuario); ?>"><?= htmlspecialchars($usuario); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>

                        <!-- Botão de Salvar -->
                        <td>
                            <button class="btn btn-sm btn-primary save-button" data-id="<?= $row['Regional']; ?>">
                                <i class="bi bi-check-circle"></i> Salvar
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>

                <script>
                document.addEventListener("DOMContentLoaded", function () {
                    document.querySelectorAll(".save-button").forEach(button => {
                        button.addEventListener("click", function () {
                            let id = this.getAttribute("data-id");
                            let gnv = document.querySelector(`select[data-id="${id}"][data-column="GNV"]`).value;
                            let nomeRegional = document.querySelector(`select[data-id="${id}"][data-column="NomeRegional"]`).value;
                            let analista = document.querySelector(`select[data-id="${id}"][data-column="Analista"]`).value;

                            fetch("/forecast/views/process_update_comercial.php", {
                                method: "POST",
                                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                                body: new URLSearchParams({
                                    regional: id,
                                    gnv: gnv,
                                    nomeRegional: nomeRegional,
                                    analista: analista
                                })
                            })
                            .then(response => response.text())  // Alterado para .text() para ver se está retornando HTML
                            .then(data => {
                                console.log("Resposta Completa do Servidor:", data);
                                try {
                                    let jsonData = JSON.parse(data);
                                    if (jsonData.success) {
                                        alert("Registro atualizado com sucesso!");
                                        location.reload();
                                    } else {
                                        alert("Erro ao atualizar: " + (jsonData.message || "Erro desconhecido"));
                                    }
                                } catch (e) {
                                    console.error("Erro ao converter JSON:", e, "Resposta do servidor:", data);
                                    alert("Erro inesperado ao processar a resposta do servidor.");
                                }
                            })
                            .catch(error => {
                                console.error("Erro na requisição:", error);
                                alert("Erro ao conectar ao servidor.");
                            });
                        });
                    });
                });
                </script>
                <script>
                document.addEventListener("DOMContentLoaded", function () {
                    document.querySelectorAll(".update-field").forEach(select => {
                        // Define o estilo inicial ao carregar a página
                        verificarMudanca(select);

                        select.addEventListener("change", function () {
                            verificarMudanca(this);
                        });
                    });

                    function verificarMudanca(select) {
                        let currentValue = select.getAttribute("data-current-value");
                        if (select.value !== currentValue) {
                            select.style.backgroundColor = "#f8d7da"; // Rosa claro
                        } else {
                            select.style.backgroundColor = ""; // Reseta o fundo
                        }
                    }
                });
                </script>


            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
