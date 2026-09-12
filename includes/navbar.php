<nav class="navbar navbar-expand-lg bg-body border-bottom shadow-sm">
    <div class="container">

        <a class="navbar-brand fw-bold fs-3" href="/UniHub/index.php">UniHub</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarUniHub" aria-controls="navbarUniHub" aria-expanded="false" aria-label="Abrir navegação">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarUniHub">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item">
                    <a class="nav-link" href="/UniHub/index.php">Início</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="/UniHub/pages/anuncios/imoveis.php">Imóveis</a>
                </li>

                <?php if (isset($_SESSION['id_usuario'])): ?>

                    <li class="nav-link">
                        <a class="nav-link" href="/UniHub/pages/conta/minha-conta.php">Minha Conta</a>
                    </li>

                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-primary" href="/UniHub/pages/anuncios/novo-anuncio.php"> Anunciar imóvel</a>
                    </li>

                    <li class="nav-link">
                        <a class="btn btn-outline-danger" href="/UniHub/pages/auth/logout.php">Sair</a>
                    </li>

                <?php else: ?>

                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-outline-primary" href="/UniHub/pages/auth/login.php">Entrar</a>
                    </li>

                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-primary" href="/UniHub/pages/auth/cadastro.php">Cadastre-se</a>
                    </li>

                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>