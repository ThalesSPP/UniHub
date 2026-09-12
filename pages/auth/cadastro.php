<?php 
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    if(isset($_SESSION['id_usuario'])){
        header('Location: /UniHub/index.php');
        exit;
    }

    $titulo = 'UniHub - Cadastre-se';
    $dadosCadastro = $_SESSION['dados_cadastro'] ?? [];

    unset($_SESSION['dados_cadastro']);
    ob_start();
?>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-9 col-lg-6">
            <div class="card auth-card">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <h1 class="h3 fw-bold">
                            Crie sua conta
                        </h1>

                        <p class="text-body-secondary mb-0">
                            Cadastre-se como anunciante para publicar seus imóveis no UniHub.
                        </p>
                    </div>

                    <?php if (isset($_SESSION['erro'])): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($_SESSION['erro']) ?>
                        </div>
                        <?php unset($_SESSION['erro']); ?>
                    <?php endif; ?>

                    <form action="/UniHub/actions/auth/cadastrar-usuario.php" method="POST">
                        <div class="mb-3">
                            <label for="nome" class="form-label">
                                Nome completo
                            </label>

                            <input type="text" class="form-control" id="nome" name="nome" placeholder="Digite seu nome completo" value="<?= htmlspecialchars($dadosCadastro['nome'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                E-mail
                            </label>

                            <input type="email" class="form-control"id="email"name="email"placeholder="seuemail@exemplo.com" value="<?= htmlspecialchars($dadosCadastro['email'] ?? '') ?>" required autocomplete="email">
                        </div>

                        <div class="mb-3">
                            <label for="telefone" class="form-label">
                                Telefone
                            </label>

                            <input type="tel" class="form-control" id="telefone" name="telefone" placeholder="+55 (28) 99999-9999" value="<?= htmlspecialchars($dadosCadastro['telefone'] ?? '') ?>" required autocomplete="tel">
                        </div>

                        <div class="mb-3">
                            <label for="senha" class="form-label">
                                Senha
                            </label>

                            <input type="password" class="form-control" id="senha" name="senha" placeholder="Digite sua senha" required autocomplete="new-password">
                        </div>

                        <div class="mb-3">

                            <label for="confirmar_senha" class="form-label">
                                Confirmar senha
                            </label>

                            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" placeholder="Digite sua senha novamente" required autocomplete="new-password">
                        </div>

                        <div class="d-grid mt-4">

                            <button type="submit" class="btn btn-primary">
                                Criar conta
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">
                    <div class="text-center">
                        <p class="mb-2">
                            Já possui uma conta?
                        </p>

                        <a href="/UniHub/pages/auth/login.php" class="text-decoration-none">Entrar no UniHub</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php 
    $conteudo = ob_get_clean();
    require __DIR__ . '/../../layouts/master.php';
?>