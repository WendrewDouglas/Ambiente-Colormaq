<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php?error=unauthorized");
    exit();
}

// Função para verificar permissões
function verificarPermissao($requerido) {
    $usuarioRole = $_SESSION['user_role'] ?? 'consulta'; 

    $permissoes = [
        'admin' => ['dashboard', 'users', 'consulta_lancamentos', 'apontar_forecast', 'configuracoes', 'depara_comercial'],
        'gestor' => ['dashboard', 'apontar_forecast', 'configuracoes', 'depara_comercial'],
        'consulta' => ['dashboard', 'configuracoes']
    ];

    if (!in_array($requerido, $permissoes[$usuarioRole])) {
        header("Location: index.php?page=dashboard&error=permissao_negada");
        exit();
    }
}
?>
