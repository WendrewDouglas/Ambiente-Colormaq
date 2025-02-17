<div class="sidebar d-flex flex-column justify-content-between">
    <!-- Logo -->
    <div class="text-center mb-3">
        <img src="/forecast/public/assets/img/logo_color2.png" alt="Logo da Empresa" class="img-fluid" style="max-width: 150px;">
    </div>

    <!-- Menu Principal -->
    <div class="nav flex-column">
        <!-- Dashboard -->
        <a href="index.php?page=dashboard" class="nav-link">🏠 Dashboard</a>

        <!-- Menu Forecast com Dropdown -->
        <div class="nav-item">
            <a href="#forecastSubmenu" class="nav-link dropdown-toggle" data-bs-toggle="collapse" aria-expanded="false" aria-controls="forecastSubmenu">
                📊 Forecast
            </a>
            <div class="collapse ms-3" id="forecastSubmenu">
                <a href="index.php?page=apontar_forecast" class="nav-link">🛒 Apontar Forecast</a>
                <a href="index.php?page=historico_forecast" class="nav-link">🕒 Apontamentos</a>
                <a href="index.php?page=consulta_lancamentos" class="nav-link">📋 Relatório PCP</a>
            </div>
        </div>

        <!-- Enviar Sell-Out -->
        <li class="nav-item">
            <a class="nav-link" href="index.php?page=enviar_sellout">
                <i class="bi bi-upload"></i> Enviar Sell-Out
            </a>
        </li>

        <!-- Menu Financeiro com Dropdown -->
        <div class="nav-item">
            <a href="#financeiroSubmenu" class="nav-link dropdown-toggle" data-bs-toggle="collapse" aria-expanded="false" aria-controls="financeiroSubmenu">
                💰 Financeiro
            </a>
            <div class="collapse ms-3" id="financeiroSubmenu">
                <a href="http://intranet.color.com.br:5000/financeiro/titulos_inadimplencia" class="nav-link">📊 Títulos e Inadimplência</a>
            </div>
        </div>

        <!-- Novo Menu Universidade Color com Dropdown -->
        <div class="nav-item">
            <a href="#universidadeColorSubmenu" class="nav-link dropdown-toggle" data-bs-toggle="collapse" aria-expanded="false" aria-controls="universidadeColorSubmenu">
                🎓 Universidade Color
            </a>
            <div class="collapse ms-3" id="universidadeColorSubmenu">
                <a href="index.php?page=uc_dashboard" class="nav-link"><i class="bi bi-person me-2"></i>Área do Aluno</a>
                <a href="index.php?page=uc_cursos" class="nav-link"><i class="bi bi-book me-2"></i>Cursos</a>
                <a href="index.php?page=uc_upload" class="nav-link"><i class="bi bi-plus-circle me-2"></i>Novo Curso</a>
                <a href="index.php?page=uc_certificados" class="nav-link"><i class="bi bi-award me-2"></i>Certificados</a>
            </div>
        </div>

                <!-- Menu Configurações com Dropdown -->
                <div class="nav-item">
            <a href="#configSubmenu" class="nav-link dropdown-toggle" data-bs-toggle="collapse" aria-expanded="false" aria-controls="configSubmenu">
                ⚙️ Configurações
            </a>
            <div class="collapse ms-3" id="configSubmenu">
                <a href="index.php?page=configuracoes" class="nav-link">🛠️ Geral</a>
                <a href="index.php?page=users" class="nav-link">👥 Gerenciar Usuários</a>
                <a href="index.php?page=depara_comercial" class="nav-link">📊 Gestores Comerciais</a>
            </div>
        </div>


    </div>

    <!-- Informações do Usuário e Botão Sair -->
    <div class="mt-auto text-center mb-3 small">
        <p class="mb-1">Logado como:</p>
        <strong><?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?></strong>
    </div>

    <a href="index.php?page=logout" class="btn btn-danger logout-btn btn-sm w-100">Sair</a>
</div>
