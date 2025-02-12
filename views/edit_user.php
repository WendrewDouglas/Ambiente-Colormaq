<?php
require_once __DIR__ . '/../includes/auto_check.php';
require_once __DIR__ . '/../includes/db_connection.php';

$pageTitle = 'Editar Usuário - Forecast System';
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<div class='alert alert-danger'>ID de usuário inválido.</div>";
    exit();
}

$user_id = $_GET['id'];
$db = new Database();
$conn = $db->getConnection();

$sql = "SELECT id, name, email, role FROM users WHERE id = ?";
$params = array($user_id);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false || !($user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC))) {
    echo "<div class='alert alert-danger'>Usuário não encontrado.</div>";
    exit();
}
?>

<div class="content">
    <h2>✏️ Editar Usuário</h2>
    <form action="index.php?page=update_user" method="POST">
        <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']); ?>">

        <div class="mb-3">
            <label for="name" class="form-label">Nome</label>
            <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="mb-3">
            <label for="role" class="form-label">Perfil</label>
            <select name="role" id="role" class="form-control" required>
                <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                <option value="gestor" <?= $user['role'] == 'gestor' ? 'selected' : ''; ?>>Gestor</option>
                <option value="consulta" <?= $user['role'] == 'consulta' ? 'selected' : ''; ?>>Consulta</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="index.php?page=users" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>
