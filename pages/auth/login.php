<?php
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    if(isset($_SESSION['id_usuario'])){
        header('Location: /UniHub/index.php');
        exit;
    }

    $titulo = 'UniHub - Entrar';
    ob_start();
?>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            <div class="card auth-card shadow-sm">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h1 class="h3 fw-bold">
                            Entrar no UniHub
                        </h1>

                        <p class="text-body-secondary mb-0">
                            Acesse sua conta para gerenciar seus anúncios.
                        </p>

                    </div>

                    <?php if (isset($_SESSION['erro'])): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($_SESSION['erro']) ?>
                        </div>

                        <?php unset($_SESSION['erro']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['sucesso'])): ?>
                        <div class="alert alert-success" role="alert">
                            <?= htmlspecialchars($_SESSION['sucesso']) ?>
                        </div>

                        <?php unset($_SESSION['sucesso']); ?>
                    <?php endif; ?>

                    <form action="/UniHub/actions/auth/autenticar.php" method="POST">
                        <div class="mb-3">
                            <label for="email"class="form-label">
                                E-mail
                            </label>

                            <input type="email" class="form-control" id="email" name="email" placeholder="seuemail@exemplo.com" value="<?= htmlspecialchars($_SESSION['email_login'] ?? '') ?>" required autocomplete="email">
                        </div>

                        <div class="mb-3">
                            <labelfor="senha" class="form-label">
                                Senha
                            </labelfor=>

                            <input type="password" class="form-control" id="senha" name="senha" placeholder="Digite sua senha" required autocomplete="current-password">
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary">
                                Entrar
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">
                    <div class="text-center">
                        <p class="mb-2">
                            Ainda não possui uma conta?
                        </p>
                        <a href="/UniHub/pages/auth/cadastro.php" class="text-decoration-none">Cadastre-se como anunciante</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
    unset($_SESSION['email_login']);

    $conteudo = ob_get_clean();
    require __DIR__ . '/../../layouts/master.php';
?>