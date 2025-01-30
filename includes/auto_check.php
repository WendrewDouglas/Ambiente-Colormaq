<?php

/*
// Verifica se a sessão já foi iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php?error=unauthorized");
    exit();
}
    */

// Verificar se a sessão já foi iniciada antes de chamar session_start()
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Verificar se a sessão do usuário existe
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/index.php?error=unauthorized");
    exit();
}

// Proteger contra roubo de sessão (User-Agent)
if (!isset($_SESSION['user_agent']) || $_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
    session_unset();
    session_destroy();
    header("Location: ../public/index.php?error=session_hijacked");
    exit();
}

// Redireciona após tempo de inatividade
$tempo_inatividade = 900; // 15 minutos
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > $tempo_inatividade)) {
    session_unset();
    session_destroy();
    header("Location: ../public/index.php?error=session_expired");
    exit();
}

// Atualiza o tempo da sessão
$_SESSION['login_time'] = time();

// Definir um valor padrão para o nome do usuário, caso não esteja na sessão
if (!isset($_SESSION['user_name'])) {
    $_SESSION['user_name'] = 'Usuário desconhecido';
}
?>
