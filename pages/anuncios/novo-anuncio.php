<?php
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    if(!isset($_SESSION['id_usuario'])){
        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }

    require_once __DIR__ . '/../../config/database.php';
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

    $dados = $_SESSION['dados_anuncio'] ?? [];
    $semNumero = strtoupper(trim($dados['numero'] ?? '')) === 'S/N';

    unset($_SESSION['dados_anuncio']);

    $titulo = 'UniHub - Anunciar imóvel';
    ob_start();

?>

<section class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <div class="mb-4">
                <h1 class="h2 fw-bold mb-1">
                    Anunciar imóvel
                </h1>

                <p class="text-body-secondary mb-1">
                    Preencha as informações da moradia que deseja anunciar.
                </p>

                <p class="text-body-secondary small mb-0">
                    Os campos marcados com <span class="text-danger fw-bold">*</span> são obrigatórios.
                </p>
            </div>

            <?php if(isset($_SESSION['erro'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($_SESSION['erro']) ?>
                </div>
                <?php unset($_SESSION['erro']); ?>
            <?php endif; ?>

            <div class="alert alert-danger d-none" id="alerta_campos_obrigatorios" role="alert">
                Preencha todos os campos obrigatórios antes de publicar o anúncio.
            </div>

            <form action="/UniHub/actions/anuncios/salvar-anuncio.php" method="POST" enctype="multipart/form-data" id="form_anuncio" novalidate>
                <div class="card auth-card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-4">
                            Informações do imóvel
                        </h2>

                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título do anúncio <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="titulo" name="titulo" maxlength="150" value="<?= htmlspecialchars($dados['titulo'] ?? '') ?>" placeholder="Ex.: Kitnet próxima ao IFES" required>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="id_tipo" class="form-label">Tipo de imóvel <span class="text-danger">*</span></label>
                                <select class="form-select" id="id_tipo" name="id_tipo" required>
                                    <option value="">Selecione</option>
                                    <?php foreach($tipos as $tipo): ?>
                                        <option value="<?= $tipo['id_tipo'] ?>" <?= (($dados['id_tipo'] ?? '') == $tipo['id_tipo']) ? 'selected' : '' ?>><?= htmlspecialchars($tipo['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="valor" class="form-label">Valor do aluguel <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="valor" name="valor" min="0" step="0.01" value="<?= htmlspecialchars($dados['valor'] ?? '') ?>" placeholder="Ex.: 750.00" required>
                            </div>

                            <div class="col-md-4">
                                <label for="estadia_minima_meses" class="form-label">
                                    Estadia mínima (meses) <span class="text-danger">*</span>
                                </label>

                                <input type="number" class="form-control" id="estadia_minima_meses" name="estadia_minima_meses" min="1" step="1" value="<?= htmlspecialchars($dados['estadia_minima_meses'] ?? '1') ?>" placeholder="Ex.: 6" required>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label for="descricao" class="form-label">Descrição <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="5" required><?= htmlspecialchars($dados['descricao'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="card auth-card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-4">
                            Endereço
                        </h2>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="logradouro" class="form-label">Logradouro <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="logradouro" name="logradouro" value="<?= htmlspecialchars($dados['logradouro'] ?? '') ?>" placeholder="Rua, avenida..." required>
                            </div>

                            <div class="col-md-4">
                                <label for="numero" class="form-label">Número <span class="text-danger">*</span></label>

                                <input type="text" class="form-control <?= $semNumero ? 'd-none' : '' ?>" id="numero" name="numero" inputmode="numeric" pattern="[0-9]+" value="<?= $semNumero ? '' : htmlspecialchars($dados['numero'] ?? '') ?>" title="Digite apenas números" <?= $semNumero ? 'disabled' : 'required' ?>>

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
                                <input type="text" class="form-control" id="complemento" name="complemento" value="<?= htmlspecialchars($dados['complemento'] ?? '') ?>" placeholder="Quarto 01, fundos, 2º andar...">
                            </div>

                            <div class="col-md-6">

                                <label for="bairro" class="form-label">Bairro <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="bairro" name="bairro" value="<?= htmlspecialchars($dados['bairro'] ?? '') ?>" required>

                            </div>

                            <div class="col-md-5">
                                <label for="cidade" class="form-label">Cidade <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cidade" name="cidade" placeholder="Ex.: Alegre" value="<?= htmlspecialchars($dados['cidade'] ?? '') ?>" required>
                            </div>

                            <div class="col-md-3">
                                <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>

                                <select class="form-select" id="estado" name="estado" required>
                                    <option value="">Selecione</option>

                                    <?php foreach($estados as $uf): ?>
                                        <option value="<?= $uf ?>" <?= ($dados['estado'] ?? '') === $uf ? 'selected' : '' ?>>
                                            <?= $uf ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="cep" class="form-label">CEP</label>
                                <input type="text" class="form-control" id="cep" name="cep" maxlength="10" value="<?= htmlspecialchars($dados['cep'] ?? '') ?>" placeholder="29500-000">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card auth-card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-2">
                            Imagens do imóvel
                        </h2>

                        <p class="text-body-secondary mb-4">
                            Selecione as fotos e escolha qual será usada como capa do anúncio.
                        </p>

                        <div class="mb-3">
                            <label for="imagens" class="form-label">Fotos do imóvel</label>
                            <input type="file" class="form-control" id="imagens" name="imagens[]" accept="image/jpeg,image/png,image/webp" multiple>

                            <div class="form-text">
                                Você pode selecionar até 10 imagens nos formatos JPG, PNG ou WEBP. Cada imagem pode ter no máximo 5 MB.
                            </div>
                        </div>

                        <div class="row g-3" id="preview_imagens"></div>
                    </div>
                </div>

                <div class="card auth-card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-4">
                            Contrato
                        </h2>

                        <div class="form-check mb-4">
                            <input type="checkbox" class="form-check-input" id="usar_contrato" name="usar_contrato" value="1" <?= !empty($dados['usar_contrato']) ? 'checked' : '' ?>>
                            <label for="usar_contrato" class="form-check-label">Disponibilizar contrato para este imóvel</label>
                        </div>

                        <div id="opcoes_contrato">
                            <h3 class="h6 fw-bold mb-3">
                                Cláusulas opcionais
                            </h3>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="clausula_animais" name="clausula_animais" value="1" <?= !empty($dados['clausula_animais']) ? 'checked' : '' ?>>
                                        <label for="clausula_animais" class="form-check-label">Não permite animais</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="clausula_caucao" name="clausula_caucao" value="1" <?= !empty($dados['clausula_caucao']) ? 'checked' : '' ?>>
                                        <label for="clausula_caucao" class="form-check-label">Exigir caução</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="clausula_agua_energia" name="clausula_agua_energia" value="1" <?= !empty($dados['clausula_agua_energia']) ? 'checked' : '' ?>>
                                        <label for="clausula_agua_energia" class="form-check-label">Água e energia por conta do inquilino</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="clausula_rescisao" name="clausula_rescisao" value="1" <?= !empty($dados['clausula_rescisao']) ? 'checked' : '' ?>>
                                        <label for="clausula_rescisao" class="form-check-label">Rescisão antecipada com multa</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="clausula_visitas" name="clausula_visitas" value="1" <?= !empty($dados['clausula_visitas']) ? 'checked' : '' ?>>
                                        <label for="clausula_visitas" class="form-check-label">Permite visitas</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="clausula_manutencao" name="clausula_manutencao" value="1" <?= !empty($dados['clausula_manutencao']) ? 'checked' : '' ?>>
                                        <label for="clausula_manutencao" class="form-check-label">Conservação e pequenos reparos por conta do inquilino</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="clausula_multa" name="clausula_multa" value="1" <?= !empty($dados['clausula_multa']) ? 'checked' : '' ?>>
                                        <label for="clausula_multa" class="form-check-label">Aplicar multas por descumprimento</label>
                                    </div>
                                </div>
                            </div>

                            <h3 class="h6 fw-bold mb-3">
                                Cláusulas adicionais
                            </h3>

                            <div id="clausulas_extras">
                                <?php if(!empty($dados['clausulas_extras'])): ?>
                                    <?php foreach($dados['clausulas_extras'] as $clausula): ?>
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
                     
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="nova_clausula" class="form-label">Nova cláusula</label>
                                <textarea class="form-control" id="nova_clausula" rows="3" placeholder="Digite uma cláusula adicional"></textarea>
                            </div>
                            <button type="button" class="btn btn-success btn-sm" id="adicionar_clausula">Adicionar cláusula</button>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Publicar anúncio</button>
                    <a href="/UniHub/index.php" class="btn btn-outline-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
    const formAnuncio = document.getElementById('form_anuncio');
    const alertaCamposObrigatorios = document.getElementById('alerta_campos_obrigatorios');

    formAnuncio.addEventListener('submit', function(evento){
        const camposObrigatorios = formAnuncio.querySelectorAll('[required]:not(:disabled)');
        let primeiroCampoInvalido = null;

        camposObrigatorios.forEach(function(campo){
            campo.classList.remove('is-invalid');

            if(!campo.checkValidity()){
                campo.classList.add('is-invalid');

                if(!primeiroCampoInvalido){
                    primeiroCampoInvalido = campo;
                }
            }
        });

        if(primeiroCampoInvalido){
            evento.preventDefault();

            alertaCamposObrigatorios.classList.remove('d-none');

            alertaCamposObrigatorios.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            });

            primeiroCampoInvalido.focus();
        }
        
        else{
            alertaCamposObrigatorios.classList.add('d-none');
        }
    });

    formAnuncio.querySelectorAll('input, select, textarea').forEach(function(campo){
        campo.addEventListener('input', function(){
            if(campo.checkValidity()){
                campo.classList.remove('is-invalid');
            }
        });

        campo.addEventListener('change', function(){
            if(campo.checkValidity()){
                campo.classList.remove('is-invalid');
            }
        });
    });

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

    imagens.addEventListener('change', function(){
        previewImagens.innerHTML = '';

        const arquivos = Array.from(imagens.files);

        arquivos.forEach(function(arquivo, indice){
            const coluna = document.createElement('div');
            coluna.className = 'col-6 col-md-4 col-lg-3';

            const card = document.createElement('div');
            card.className = 'card h-100';

            const imagem = document.createElement('img');
            imagem.className = 'card-img-top';
            imagem.style.height = '160px';
            imagem.style.objectFit = 'cover';
            imagem.alt = 'Pré-visualização da imagem';
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

            if(indice === 0){
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