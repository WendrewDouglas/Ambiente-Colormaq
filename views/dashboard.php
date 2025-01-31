<?php
// Proteção de acesso não autorizado
require_once __DIR__ . '/../includes/auto_check.php';

// Definição do título da página
$pageTitle = 'Dashboard - Forecast System';

// Inclusão do header e sidebar
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
?>

<div class="content">
    <h2>Bem-vindo ao Dashboard</h2>
    <p>Aqui você pode gerenciar seus apontamentos de forecast.</p>

    <div class="mt-4">
        <a href="index.php?page=apontar_forecast" class="btn btn-primary">📊 Apontar Forecast</a>
        <a href="index.php?page=users" class="btn btn-secondary">👥 Gerenciar Usuários</a>
        <a href="index.php?page=configuracoes" class="btn btn-info">⚙️ Configurações</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>