<?php
    if(session_status() === PHP_SESSION_NONE){

        session_start();
    }

    if(!isset($_SESSION['id_usuario'])){
        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }

    require_once __DIR__ . '/../../config/database.php';

    $idAnuncio = $_GET['id'] ?? '';
    $idUsuario = $_SESSION['id_usuario'];

    if($idAnuncio === '' || !ctype_digit($idAnuncio)){
        header('Location: /UniHub/pages/anuncios/imoveis.php');
        exit;
    }

    $sql = "
        SELECT
            a.id_anuncio,
            a.id_usuario,
            a.id_tipo,
            a.id_endereco,
            a.titulo,
            a.descricao,
            a.valor,
            e.logradouro,
            e.numero,
            e.complemento,
            e.bairro,
            e.cidade,
            e.estado,
            e.cep
        FROM anuncio a
        INNER JOIN endereco e
            ON e.id_endereco = a.id_endereco
        WHERE a.id_anuncio = ?
        AND a.id_usuario = ?
        AND a.status != 'REMOVIDO'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idAnuncio, $idUsuario]);

    $anuncio = $stmt->fetch();

    if(!$anuncio){
        header('Location: /UniHub/pages/anuncios/imoveis.php');
        exit;
    }

    $sql = "
        SELECT
            id_tipo,
            nome
        FROM tipo_anuncio
        WHERE ativo = TRUE
        ORDER BY
            CASE WHEN nome = 'OUTRO' THEN 1 ELSE 0 END,
            nome
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    $tipos = $stmt->fetchAll();

    $sql = "
        SELECT
            id_configuracao,
            usar_contrato,
            clausula_animais,
            clausula_caucao,
            clausula_agua_energia,
            clausula_rescisao,
            clausula_visitas,
            clausula_manutencao,
            clausula_multa
        FROM configuracao_contrato
        WHERE id_anuncio = ?
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idAnuncio]);

    $configuracaoContrato = $stmt->fetch();

    $clausulasExtras = [];

    if($configuracaoContrato){
        $sql = "
            SELECT
                texto
            FROM clausula_extra
            WHERE id_configuracao = ?
            ORDER BY ordem ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$configuracaoContrato['id_configuracao']]);

        $clausulasExtras = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $usarContrato = !empty($configuracaoContrato['usar_contrato']);
    $tituloPagina = 'Editar anúncio - UniHub';

    ob_start();
?>

<div class="container py-5">
    <div class="mb-4">
        <h1 class="h3 mb-1">Editar anúncio</h1>

        <p class="text-body-secondary mb-0">
            Atualize as informações da moradia anunciada.
        </p>
    </div>

    <?php if(isset($_SESSION['erro'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['erro']) ?>
        </div>

        <?php unset($_SESSION['erro']); ?>
    <?php endif; ?>

    <form action="/UniHub/actions/anuncios/atualizar-anuncio.php" method="POST">
        <input type="hidden" name="id_anuncio" value="<?= $anuncio['id_anuncio'] ?>">

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5 mb-4">Informações do imóvel</h2>

                <div class="mb-3">
                    <label for="titulo" class="form-label">Título do anúncio</label>
                    <input type="text" class="form-control" id="titulo" name="titulo" value="<?= htmlspecialchars($anuncio['titulo']) ?>" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="id_tipo" class="form-label">Tipo de imóvel</label>

                        <select class="form-select" id="id_tipo" name="id_tipo" required>
                            <?php foreach($tipos as $tipo): ?>
                                <option value="<?= $tipo['id_tipo'] ?>" <?= $anuncio['id_tipo'] == $tipo['id_tipo'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tipo['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="valor" class="form-label">Valor do aluguel</label>
                        <input type="text" class="form-control" id="valor" name="valor" value="<?= number_format($anuncio['valor'], 2, ',', '.') ?>" required>
                    </div>

                </div>

                <div>
                    <label for="descricao" class="form-label">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="5" required><?= htmlspecialchars($anuncio['descricao']) ?></textarea>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5 mb-4">Endereço</h2>

                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="logradouro" class="form-label">Logradouro</label>
                        <input type="text" class="form-control" id="logradouro" name="logradouro" value="<?= htmlspecialchars($anuncio['logradouro']) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label for="numero" class="form-label">Número</label>
                        <input type="text" class="form-control" id="numero" name="numero" value="<?= htmlspecialchars($anuncio['numero']) ?>" required>

                    </div>

                    <div class="col-md-6">

                        <label for="complemento" class="form-label">Complemento</label>
                        <input type="text" class="form-control" id="complemento" name="complemento" value="<?= htmlspecialchars($anuncio['complemento'] ?? '') ?>">

                    </div>

                    <div class="col-md-6">

                        <label for="bairro" class="form-label">Bairro</label>
                        <input type="text" class="form-control" id="bairro" name="bairro" value="<?= htmlspecialchars($anuncio['bairro']) ?>" required>
                    </div>

                    <div class="col-md-5">
                        <label for="cidade" class="form-label">Cidade</label>
                        <input type="text" class="form-control" id="cidade" name="cidade" value="<?= htmlspecialchars($anuncio['cidade']) ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label for="estado" class="form-label">Estado</label>
                        <input type="text" class="form-control" id="estado" name="estado" maxlength="2" value="<?= htmlspecialchars($anuncio['estado']) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label for="cep" class="form-label">CEP</label>
                        <input type="text" class="form-control" id="cep" name="cep" value="<?= htmlspecialchars($anuncio['cep'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5 mb-4">Contrato</h2>

                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="usar_contrato" name="usar_contrato" value="1" <?= $usarContrato ? 'checked' : '' ?>>

                    <label for="usar_contrato" class="form-check-label">
                        Disponibilizar contrato para este imóvel
                    </label>
                </div>

                <div id="opcoes_contrato">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="clausula_animais" name="clausula_animais" value="1" <?= !empty($configuracaoContrato['clausula_animais']) ? 'checked' : '' ?>>

                                <label for="clausula_animais" class="form-check-label">
                                    Não permite animais
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="clausula_caucao" name="clausula_caucao" value="1" <?= !empty($configuracaoContrato['clausula_caucao']) ? 'checked' : '' ?>>

                                <label for="clausula_caucao" class="form-check-label">
                                    Exige caução
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="clausula_agua_energia" name="clausula_agua_energia" value="1" <?= !empty($configuracaoContrato['clausula_agua_energia']) ? 'checked' : '' ?>>

                                <label for="clausula_agua_energia" class="form-check-label">
                                    Água e energia por conta do inquilino
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="clausula_rescisao" name="clausula_rescisao" value="1" <?= !empty($configuracaoContrato['clausula_rescisao']) ? 'checked' : '' ?>>

                                <label for="clausula_rescisao" class="form-check-label">
                                    Rescisão antecipada com multa
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="clausula_visitas" name="clausula_visitas" value="1" <?= !empty($configuracaoContrato['clausula_visitas']) ? 'checked' : '' ?>>

                                <label for="clausula_visitas" class="form-check-label">
                                    Permite visitas
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="clausula_manutencao" name="clausula_manutencao" value="1" <?= !empty($configuracaoContrato['clausula_manutencao']) ? 'checked' : '' ?>>

                                <label for="clausula_manutencao" class="form-check-label">
                                    Conservação e pequenos reparos por conta do inquilino
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="clausula_multa" name="clausula_multa" value="1" <?= !empty($configuracaoContrato['clausula_multa']) ? 'checked' : '' ?>>

                                <label for="clausula_multa" class="form-check-label">
                                    Aplica multas por descumprimento
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h3 class="h6">Cláusulas adicionais</h3>

                    <div id="clausulas_extras">
                        <?php foreach($clausulasExtras as $clausula): ?>
                            <div class="d-flex gap-2 mb-3 clausula-extra">
                                <textarea class="form-control" name="clausulas_extras[]" rows="3" readonly><?= htmlspecialchars($clausula) ?></textarea>

                                <button type="button" class="btn btn-outline-danger excluir-clausula">
                                    Excluir
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mb-2">
                        <textarea class="form-control" id="nova_clausula" rows="3" placeholder="Digite uma nova cláusula adicional"></textarea>
                    </div>

                    <button type="button" class="btn btn-success btn-sm" id="adicionar_clausula">
                        Adicionar cláusula
                    </button>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="/UniHub/pages/anuncios/detalhes-anuncio.php?id=<?= $anuncio['id_anuncio'] ?>" class="btn btn-outline-secondary">
                Cancelar
            </a>

            <button type="submit" class="btn btn-primary">
                Salvar alterações
            </button>
        </div>
    </form>
</div>

<script>
    const usarContrato = document.getElementById('usar_contrato');
    const opcoesContrato = document.getElementById('opcoes_contrato');
    const adicionarClausula = document.getElementById('adicionar_clausula');
    const clausulasExtras = document.getElementById('clausulas_extras');
    const novaClausula = document.getElementById('nova_clausula');

    function atualizarContrato(){
        if(usarContrato.checked){
            opcoesContrato.style.display = 'block';
        }
        
        else{
            opcoesContrato.style.display = 'none';
        }
    }

    usarContrato.addEventListener('change', atualizarContrato);
    atualizarContrato();

    adicionarClausula.addEventListener('click', function(){
        const texto = novaClausula.value.trim();

        if(texto === ''){
            return;
        }

        const campo = document.createElement('div');
        campo.className = 'd-flex gap-2 mb-3 clausula-extra';

        const textarea = document.createElement('textarea');
        textarea.className = 'form-control';
        textarea.name = 'clausulas_extras[]';
        textarea.rows = 3;
        textarea.readOnly = true;
        textarea.value = texto;

        const botaoExcluir = document.createElement('button');
        botaoExcluir.type = 'button';
        botaoExcluir.className = 'btn btn-outline-danger excluir-clausula';
        botaoExcluir.textContent = 'Excluir';

        campo.appendChild(textarea);
        campo.appendChild(botaoExcluir);

        clausulasExtras.appendChild(campo);

        novaClausula.value = '';
        novaClausula.focus();
    });

    clausulasExtras.addEventListener('click', function(evento){
        if(evento.target.classList.contains('excluir-clausula')){
            evento.target.closest('.clausula-extra').remove();

        }
    });
</script>

<?php
    $conteudo = ob_get_clean();
    require __DIR__ . '/../../layouts/master.php';
?>