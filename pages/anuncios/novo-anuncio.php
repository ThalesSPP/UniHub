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
    $dados = $_SESSION['dados_anuncio'] ?? [];

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

                <p class="text-body-secondary mb-0">
                    Preencha as informações da moradia que deseja anunciar.
                </p>
            </div>

            <?php if(isset($_SESSION['erro'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($_SESSION['erro']) ?>
                </div>
                <?php unset($_SESSION['erro']); ?>
            <?php endif; ?>

            <form action="/UniHub/actions/anuncios/salvar-anuncio.php" method="POST" enctype="multipart/form-data">
                <div class="card auth-card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-4">
                            Informações do imóvel
                        </h2>

                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título do anúncio</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" maxlength="150" value="<?= htmlspecialchars($dados['titulo'] ?? '') ?>" placeholder="Ex.: Kitnet próxima ao IFES" required>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="id_tipo" class="form-label">Tipo de imóvel</label>
                                <select class="form-select" id="id_tipo" name="id_tipo" required>
                                    <option value="">Selecione</option>
                                    <?php foreach($tipos as $tipo): ?>
                                        <option value="<?= $tipo['id_tipo'] ?>" <?= (($dados['id_tipo'] ?? '') == $tipo['id_tipo']) ? 'selected' : '' ?>><?= htmlspecialchars($tipo['nome']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="valor" class="form-label">Valor do aluguel</label>
                                <input type="number" class="form-control" id="valor" name="valor" min="0" step="0.01" value="<?= htmlspecialchars($dados['valor'] ?? '') ?>" placeholder="Ex.: 750.00" required>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label for="descricao" class="form-label">Descrição</label>
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
                                <label for="logradouro" class="form-label">Logradouro</label>
                                <input type="text" class="form-control" id="logradouro" name="logradouro" value="<?= htmlspecialchars($dados['logradouro'] ?? '') ?>" placeholder="Rua, avenida..." required>
                            </div>

                            <div class="col-md-4">
                                <label for="numero" class="form-label">Número</label>
                                <input type="text" class="form-control" id="numero" name="numero" value="<?= htmlspecialchars($dados['numero'] ?? '') ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="complemento" class="form-label">Complemento</label>
                                <input type="text" class="form-control" id="complemento" name="complemento" value="<?= htmlspecialchars($dados['complemento'] ?? '') ?>" placeholder="Quarto 01, fundos, 2º andar...">
                            </div>

                            <div class="col-md-6">

                                <label for="bairro" class="form-label">Bairro</label>
                                <input type="text" class="form-control" id="bairro" name="bairro" value="<?= htmlspecialchars($dados['bairro'] ?? '') ?>" required>

                            </div>

                            <div class="col-md-5">
                                <label for="cidade" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="cidade" name="cidade" value="<?= htmlspecialchars($dados['cidade'] ?? 'Alegre') ?>" required>
                            </div>

                            <div class="col-md-3">

                                <label for="estado" class="form-label">Estado</label>
                                <input type="text" class="form-control" id="estado" name="estado" maxlength="2" value="<?= htmlspecialchars($dados['estado'] ?? 'ES') ?>" required>
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
                        <h2 class="h5 fw-bold mb-4">
                            Imagens do imóvel
                        </h2>

                        <div class="mb-3">
                            <label for="imagens" class="form-label">Fotos do imóvel</label>
                            <input type="file" class="form-control" id="imagens" name="imagens[]" accept="image/jpeg,image/png,image/webp" multiple>

                            <div class="form-text">
                                Você pode selecionar até 10 imagens nos formatos JPG, PNG ou WEBP. Cada imagem pode ter no máximo 5 MB.
                            </div>
                        </div>
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
                                        <div class="d-flex gap-2 mb-3 clausula-extra">
                                            <textarea class="form-control" name="clausulas_extras[]" rows="3" readonly><?= htmlspecialchars($clausula) ?></textarea>
                                            <button type="button" class="btn btn-outline-danger excluir-clausula">Excluir</button>
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