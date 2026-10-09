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
            a.estadia_minima_meses,
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

    $estados = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF',
        'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
        'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS',
        'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
    ];

    $sql = "
        SELECT
            id_imagem,
            caminho_arquivo,
            ordem,
            principal
        FROM imagem_anuncio
        WHERE id_anuncio = ?
        ORDER BY
            principal DESC,
            ordem ASC,
            id_imagem ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idAnuncio]);

    $imagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    $semNumero = strtoupper(trim($anuncio['numero'])) === 'S/N';
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

    <form action="/UniHub/actions/anuncios/atualizar-anuncio.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_anuncio" value="<?= $anuncio['id_anuncio'] ?>">

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5 mb-4">Informações do imóvel</h2>

                <div class="mb-3">
                    <label for="titulo" class="form-label">Título do anúncio</label>
                    <input type="text" class="form-control" id="titulo" name="titulo" value="<?= htmlspecialchars($anuncio['titulo']) ?>" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="id_tipo" class="form-label">Tipo de imóvel</label>

                        <select class="form-select" id="id_tipo" name="id_tipo" required>
                            <?php foreach($tipos as $tipo): ?>
                                <option value="<?= $tipo['id_tipo'] ?>" <?= $anuncio['id_tipo'] == $tipo['id_tipo'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($tipo['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="valor" class="form-label">Valor do aluguel</label>
                        <input type="text" class="form-control" id="valor" name="valor" value="<?= number_format($anuncio['valor'], 2, ',', '.') ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label for="estadia_minima_meses" class="form-label">
                            Estadia mínima (meses)
                        </label>

                        <input type="number" class="form-control" id="estadia_minima_meses" name="estadia_minima_meses" min="1" step="1" value="<?= htmlspecialchars($anuncio['estadia_minima_meses']) ?>" required>
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

                        <input type="text" class="form-control <?= $semNumero ? 'd-none' : '' ?>" id="numero" name="numero" inputmode="numeric" pattern="[0-9]+" value="<?= $semNumero ? '' : htmlspecialchars($anuncio['numero']) ?>" title="Digite apenas números" <?= $semNumero ? 'disabled' : 'required' ?>>

                        <input type="text" class="form-control <?= $semNumero ? '' : 'd-none' ?>" id="numero_sem_numero" name="numero" value="S/N" readonly <?= $semNumero ? '' : 'disabled' ?>>

                        <div class="form-check mt-2">
                            <input type="checkbox" class="form-check-input" id="sem_numero" <?= $semNumero ? 'checked' : '' ?>>

                            <label class="form-check-label" for="sem_numero">
                                Imóvel sem número
                            </label>
                        </div>
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

                        <select class="form-select" id="estado" name="estado" required>
                            <option value="">Selecione</option>

                            <?php foreach($estados as $uf): ?>
                                <option value="<?= $uf ?>" <?= $anuncio['estado'] === $uf ? 'selected' : '' ?>>
                                    <?= $uf ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                <h2 class="h5 mb-2">Imagens do anúncio</h2>

                <p class="text-body-secondary mb-4">
                    Remova imagens cadastradas ou adicione novas fotos ao anúncio.
                </p>

                <?php if(!empty($imagens)): ?>
                    <div class="row g-3 mb-4">
                        <?php foreach($imagens as $imagem): ?>
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="card h-100">
                                    <img src="/UniHub/<?= htmlspecialchars($imagem['caminho_arquivo']) ?>" class="card-img-top" style="height: 160px; object-fit: cover;" alt="Imagem do anúncio">

                                    <div class="card-body p-2">
                                        <?php if($imagem['principal']): ?>
                                            <span class="badge text-bg-primary mb-2">
                                                Capa atual
                                            </span>
                                        <?php endif; ?>

                                        <input type="radio" class="btn-check" name="imagem_principal" id="imagem_principal_existente_<?= $imagem['id_imagem'] ?>" value="existente:<?= $imagem['id_imagem'] ?>" autocomplete="off" <?= $imagem['principal'] ? 'checked' : '' ?>>

                                        <label class="btn btn-outline-primary btn-sm w-100 mb-2" for="imagem_principal_existente_<?= $imagem['id_imagem'] ?>">
                                            Definir como capa
                                        </label>

                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input remover-imagem" id="remover_imagem_<?= $imagem['id_imagem'] ?>" name="imagens_remover[]" value="<?= $imagem['id_imagem'] ?>" data-radio-capa="imagem_principal_existente_<?= $imagem['id_imagem'] ?>">

                                            <label class="form-check-label" for="remover_imagem_<?= $imagem['id_imagem'] ?>">
                                                Remover imagem
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                <?php else: ?>
                    <p class="text-body-secondary mb-4">
                        Este anúncio não possui imagens cadastradas.
                    </p>
                <?php endif; ?>

                <div>
                    <label for="imagens" class="form-label">
                        Adicionar novas imagens
                    </label>

                    <input type="file" class="form-control" id="imagens" name="imagens[]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" multiple>

                    <div class="form-text">
                        O anúncio pode possuir no máximo 10 imagens. Envie arquivos JPG, PNG ou WEBP de até 5 MB cada.
                    </div>

                    <div class="row g-3 mt-1" id="preview_imagens"></div>
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
                            <div class="d-flex flex-column flex-sm-row gap-2 mb-3 clausula-extra">
                                <textarea class="form-control" name="clausulas_extras[]" rows="3" readonly><?= htmlspecialchars($clausula) ?></textarea>

                                <div class="d-flex flex-sm-column gap-2">
                                    <button type="button" class="btn btn-outline-primary editar-clausula">
                                        Editar
                                    </button>

                                    <button type="button" class="btn btn-outline-danger excluir-clausula">
                                        Excluir
                                    </button>
                                </div>
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
    const numero = document.getElementById('numero');
    const numeroSemNumero = document.getElementById('numero_sem_numero');
    const semNumero = document.getElementById('sem_numero');
    const imagens = document.getElementById('imagens');
    const previewImagens = document.getElementById('preview_imagens');

    function atualizarNumero(){
        if(semNumero.checked){
            numero.value = '';
            numero.classList.add('d-none');
            numero.disabled = true;
            numero.required = false;

            numeroSemNumero.classList.remove('d-none');
            numeroSemNumero.disabled = false;
        }

        else{
            numero.classList.remove('d-none');
            numero.disabled = false;
            numero.required = true;

            numeroSemNumero.classList.add('d-none');
            numeroSemNumero.disabled = true;
        }
    }

    numero.addEventListener('input', function(){
        numero.value = numero.value.replace(/\D/g, '');
    });

    semNumero.addEventListener('change', atualizarNumero);
    atualizarNumero();

    document.querySelectorAll('.remover-imagem').forEach(function(checkbox){
        checkbox.addEventListener('change', function(){
            const radio = document.getElementById(checkbox.dataset.radioCapa);

            if(radio){
                radio.disabled = checkbox.checked;

                if(checkbox.checked && radio.checked){
                    radio.checked = false;

                    const primeiraCapa = document.querySelector('input[name="imagem_principal"]:not(:disabled)');

                    if(primeiraCapa){
                        primeiraCapa.checked = true;
                    }
                }
            }
        });
    });

    imagens.addEventListener('change', function(){
        previewImagens.innerHTML = '';

        const arquivos = Array.from(imagens.files);
        const capaSelecionada = document.querySelector('input[name="imagem_principal"]:checked');

        arquivos.forEach(function(arquivo, indice){
            const coluna = document.createElement('div');
            coluna.className = 'col-6 col-md-4 col-lg-3';

            const card = document.createElement('div');
            card.className = 'card h-100';

            const imagem = document.createElement('img');
            imagem.className = 'card-img-top';
            imagem.style.height = '160px';
            imagem.style.objectFit = 'cover';
            imagem.alt = 'Pré-visualização da nova imagem';
            imagem.src = URL.createObjectURL(arquivo);

            const cardBody = document.createElement('div');
            cardBody.className = 'card-body p-2';

            const radio = document.createElement('input');
            radio.type = 'radio';
            radio.className = 'btn-check';
            radio.name = 'imagem_principal';
            radio.id = 'imagem_principal_nova_' + indice;
            radio.value = 'nova:' + indice;
            radio.autocomplete = 'off';

            if(!capaSelecionada && indice === 0){
                radio.checked = true;
            }

            const label = document.createElement('label');
            label.className = 'btn btn-outline-primary btn-sm w-100';
            label.htmlFor = radio.id;
            label.textContent = 'Definir como capa';

            cardBody.appendChild(radio);
            cardBody.appendChild(label);
            card.appendChild(imagem);
            card.appendChild(cardBody);
            coluna.appendChild(card);
            previewImagens.appendChild(coluna);
        });
    });

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
        campo.className = 'd-flex flex-column flex-sm-row gap-2 mb-3 clausula-extra';

        const textarea = document.createElement('textarea');
        textarea.className = 'form-control';
        textarea.name = 'clausulas_extras[]';
        textarea.rows = 3;
        textarea.readOnly = true;
        textarea.value = texto;

        const acoes = document.createElement('div');
        acoes.className = 'd-flex flex-sm-column gap-2';

        const botaoEditar = document.createElement('button');
        botaoEditar.type = 'button';
        botaoEditar.className = 'btn btn-outline-primary editar-clausula';
        botaoEditar.textContent = 'Editar';

        const botaoExcluir = document.createElement('button');
        botaoExcluir.type = 'button';
        botaoExcluir.className = 'btn btn-outline-danger excluir-clausula';
        botaoExcluir.textContent = 'Excluir';

        acoes.appendChild(botaoEditar);
        acoes.appendChild(botaoExcluir);

        campo.appendChild(textarea);
        campo.appendChild(acoes);

        clausulasExtras.appendChild(campo);

        novaClausula.value = '';
        novaClausula.focus();
    });

    clausulasExtras.addEventListener('click', function(evento){
        if(evento.target.classList.contains('editar-clausula')){
            const campo = evento.target.closest('.clausula-extra');
            const textarea = campo.querySelector('textarea');

            textarea.readOnly = !textarea.readOnly;

            if(textarea.readOnly){
                evento.target.textContent = 'Editar';
                evento.target.classList.remove('btn-primary');
                evento.target.classList.add('btn-outline-primary');
            }

            else{
                evento.target.textContent = 'Concluir';
                evento.target.classList.remove('btn-outline-primary');
                evento.target.classList.add('btn-primary');
                textarea.focus();
            }
        }

        if(evento.target.classList.contains('excluir-clausula')){
            evento.target.closest('.clausula-extra').remove();
        }
    });
</script>

<?php
    $conteudo = ob_get_clean();
    require __DIR__ . '/../../layouts/master.php';
?>