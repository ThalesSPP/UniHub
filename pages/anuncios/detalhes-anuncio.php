<?php
    if(session_status() === PHP_SESSION_NONE){

        session_start();
    }

    require_once __DIR__ . '/../../config/database.php';

    $idAnuncio = $_GET['id'] ?? '';

    if($idAnuncio === '' || !ctype_digit($idAnuncio)){
        header('Location: /UniHub/pages/anuncios/imoveis.php');
        exit;
    }

    $sql = "
        SELECT
            a.id_anuncio,
            a.id_usuario,
            a.titulo,
            a.descricao,
            a.valor,
            a.status,
            a.data_cadastro,
            t.nome AS tipo,
            e.logradouro,
            e.numero,
            e.complemento,
            e.bairro,
            e.cidade,
            e.estado,
            e.cep,
            u.nome AS anunciante,
            u.email,
            u.telefone
        FROM anuncio a
        INNER JOIN tipo_anuncio t
            ON t.id_tipo = a.id_tipo
        INNER JOIN endereco e
            ON e.id_endereco = a.id_endereco
        INNER JOIN usuario u
            ON u.id_usuario = a.id_usuario
        WHERE a.id_anuncio = ?
        AND a.status = 'ATIVO'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idAnuncio]);

    $anuncio = $stmt->fetch();

    if(!$anuncio){
        header('Location: /UniHub/pages/anuncios/imoveis.php');
        exit;
    }

    $sql = "
        SELECT
            id_imagem,
            caminho_arquivo,
            ordem,
            principal
        FROM imagem_anuncio
        WHERE id_anuncio = ?
        ORDER BY principal DESC, ordem ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idAnuncio]);

    $imagens = $stmt->fetchAll();

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
        AND usar_contrato = TRUE
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idAnuncio]);

    $configuracaoContrato = $stmt->fetch();

    $clausulasExtras = [];

    if($configuracaoContrato){
        $sql = "
            SELECT
                texto,
                ordem
            FROM clausula_extra
            WHERE id_configuracao = ?
            ORDER BY ordem ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$configuracaoContrato['id_configuracao']]);

        $clausulasExtras = $stmt->fetchAll();
    }

    $valorFormatado = number_format(
        $anuncio['valor'],
        2,
        ',',
        '.'
    );

    $endereco = $anuncio['logradouro'] . ', ' . $anuncio['numero'];

    if(!empty($anuncio['complemento'])){
        $endereco .= ' - ' . $anuncio['complemento'];
    }

    $endereco .= ' - ' . $anuncio['bairro'];
    $endereco .= ', ' . $anuncio['cidade'] . '/' . $anuncio['estado'];

    if(!empty($anuncio['cep'])){
        $endereco .= ' - CEP ' . $anuncio['cep'];
    }

    $tituloPagina = $anuncio['titulo'] . ' - UniHub';

    ob_start();
?>

<div class="container py-5">
    <?php if(isset($_SESSION['sucesso'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['sucesso']) ?>
        </div>

        <?php unset($_SESSION['sucesso']); ?>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <?php if(!empty($imagens)): ?>
                <div class="galeria-anuncio mb-4">
                    <div class="imagem-principal-container" data-bs-toggle="modal" data-bs-target="#modalImagem">
                        <img src="/UniHub/<?= htmlspecialchars($imagens[0]['caminho_arquivo']) ?>" id="imagemPrincipal" class="imagem-principal-anuncio" alt="Imagem principal do imóvel">
                    </div>

                    <?php if(count($imagens) > 1): ?>
                        <div class="miniaturas-container mt-3">
                            <?php foreach($imagens as $indice => $imagem): ?>
                                <button type="button" class="miniatura-anuncio <?= $indice === 0 ? 'ativa' : '' ?>" data-imagem="/UniHub/<?= htmlspecialchars($imagem['caminho_arquivo']) ?>">
                                    <img src="/UniHub/<?= htmlspecialchars($imagem['caminho_arquivo']) ?>" alt="Imagem <?= $indice + 1 ?> do imóvel">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="modal fade" id="modalImagem" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-xl">
                        <div class="modal-content bg-transparent border-0">
                            <div class="modal-body p-0 position-relative">
                                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 botao-fechar-imagem" data-bs-dismiss="modal" aria-label="Fechar"></button>
                                
                                <img src="/UniHub/<?= htmlspecialchars($imagens[0]['caminho_arquivo']) ?>" id="imagemAmpliada" class="imagem-ampliada" alt="Imagem ampliada do imóvel">
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="sem-imagem-anuncio border rounded d-flex align-items-center justify-content-center mb-4">
                    <span class="text-body-secondary">
                        Este anúncio não possui imagens.
                    </span>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <span class="badge text-bg-primary mb-2">
                                <?= htmlspecialchars($anuncio['tipo']) ?>
                            </span>

                            <h1 class="h3 mb-1">
                                <?= htmlspecialchars($anuncio['titulo']) ?>
                            </h1>

                            <p class="text-body-secondary mb-0">
                                <?= htmlspecialchars($anuncio['cidade']) ?>/<?= htmlspecialchars($anuncio['estado']) ?>
                            </p>
                        </div>

                        <div class="text-end">
                            <span class="text-body-secondary d-block">
                                Aluguel
                            </span>

                            <strong class="fs-4">
                                R$ <?= $valorFormatado ?>
                            </strong>

                            <span class="text-body-secondary">
                                / mês
                            </span>
                        </div>
                    </div>

                    <hr>

                    <h2 class="h5">Descrição</h2>
                    <p class="mb-0">
                        <?= nl2br(htmlspecialchars($anuncio['descricao'])) ?>
                    </p>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h5 mb-3">
                        Endereço
                    </h2>

                    <p class="mb-0">
                        <?= htmlspecialchars($endereco) ?>
                    </p>
                </div>
            </div>

            <?php if($configuracaoContrato): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">
                                Condições da locação
                            </h2>

                            <span class="badge text-bg-success">
                                Contrato disponível
                            </span>
                        </div>

                        <div class="list-group list-group-flush">
                            <?php if($configuracaoContrato['clausula_animais']): ?>
                                <div class="list-group-item px-0">
                                    <strong>Animais:</strong> não são permitidos animais no imóvel.
                                </div>
                            <?php endif; ?>

                            <?php if($configuracaoContrato['clausula_caucao']): ?>
                                <div class="list-group-item px-0">
                                    <strong>Caução:</strong> será exigido pagamento de caução antecipado.
                                </div>
                            <?php endif; ?>

                            <?php if($configuracaoContrato['clausula_agua_energia']): ?>
                                <div class="list-group-item px-0">
                                    <strong>Água e energia:</strong> os pagamentos ficam sob responsabilidade do inquilino.
                                </div>
                            <?php endif; ?>

                            <?php if($configuracaoContrato['clausula_rescisao']): ?>
                                <div class="list-group-item px-0">
                                    <strong>Rescisão antecipada:</strong> poderá haver multa pela saída antecipada conforme estabelecido no contrato.
                                </div>
                            <?php endif; ?>

                            <?php if($configuracaoContrato['clausula_visitas']): ?>
                                <div class="list-group-item px-0">
                                    <strong>Visitas:</strong> é permitido receber visitantes no imóvel.
                                </div>
                            <?php endif; ?>

                            <?php if($configuracaoContrato['clausula_manutencao']): ?>
                                <div class="list-group-item px-0">
                                    <strong>Manutenção:</strong> conservação, danos causados pelo inquilino e pequenos reparos ficam sob sua responsabilidade.
                                </div>
                            <?php endif; ?>

                            <?php if($configuracaoContrato['clausula_multa']): ?>
                                <div class="list-group-item px-0">
                                    <strong>Multas:</strong> o inquilino estará sujeito às multas previstas no contrato em caso de descumprimento.
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if(!empty($clausulasExtras)): ?>
                            <h3 class="h6 mt-4">
                                Cláusulas adicionais
                            </h3>

                            <div class="list-group list-group-numbered">
                                <?php foreach($clausulasExtras as $clausula): ?>
                                    <div class="list-group-item">
                                        <?= nl2br(htmlspecialchars($clausula['texto'])) ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h2 class="h5 mb-3">
                        Anunciante
                    </h2>

                    <p class="mb-2">
                        <strong>Nome</strong><br>

                        <?= htmlspecialchars($anuncio['anunciante']) ?>
                    </p>

                    <p class="mb-2">
                        <strong>Telefone</strong><br>

                        <?= htmlspecialchars($anuncio['telefone']) ?>
                    </p>

                    <p class="mb-0">
                        <strong>E-mail</strong><br>

                        <?= htmlspecialchars($anuncio['email']) ?>
                    </p>
                </div>
            </div>

            <?php if(
                isset($_SESSION['id_usuario']) &&
                $_SESSION['id_usuario'] == $anuncio['id_usuario']
            ): ?>
                <div class="card mt-3">
                    <div class="card-body">
                        <p class="text-body-secondary small mb-3">
                            Este anúncio pertence a você.
                        </p>

                        <a href="/UniHub/pages/anuncios/editar-anuncio.php?id=<?= $anuncio['id_anuncio'] ?>" class="btn btn-outline-primary w-100">
                            Editar anúncio
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const imagemPrincipal = document.getElementById('imagemPrincipal');
    const imagemAmpliada = document.getElementById('imagemAmpliada');
    const miniaturas = document.querySelectorAll('.miniatura-anuncio');

    miniaturas.forEach(function(miniatura){
        miniatura.addEventListener('click', function(){
            const imagem = miniatura.dataset.imagem;
            
            imagemPrincipal.src = imagem;
            imagemAmpliada.src = imagem;

            miniaturas.forEach(function(item){
                item.classList.remove('ativa');
            });

            miniatura.classList.add('ativa');
        });
    });
</script>

<?php
    $conteudo = ob_get_clean();
    require __DIR__ . '/../../layouts/master.php';
?>