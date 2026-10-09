<?php
    if(session_status() === PHP_SESSION_NONE){
        session_start();
    }

    if(!isset($_SESSION['id_usuario'])){
        header('Location: /UniHub/pages/auth/login.php');
        exit;
    }

    if($_SERVER['REQUEST_METHOD'] !== 'POST'){
        header('Location: /UniHub/pages/conta/minha-conta.php');
        exit;
    }

    require_once __DIR__ . '/../../config/database.php';

    function voltarComErro($mensagem){
        $_SESSION['erro'] = $mensagem;
        header('Location: /UniHub/pages/conta/minha-conta.php');
        exit;
    }

    function campoPost($nome){
        return trim((string) ($_POST[$nome] ?? ''));
    }

    function somenteDigitos($valor){
        return preg_replace('/\D+/', '', (string) $valor);
    }

    function formatarCpf($cpf){
        $cpf = somenteDigitos($cpf);

        if(strlen($cpf) !== 11){
            return $cpf;
        }

        return substr($cpf, 0, 3) . '.' .
            substr($cpf, 3, 3) . '.' .
            substr($cpf, 6, 3) . '-' .
            substr($cpf, 9, 2);
    }

    function normalizarNomeArquivo($texto){
        $texto = trim((string) $texto);

        $textoSemAcento = iconv(
            'UTF-8',
            'ASCII//TRANSLIT//IGNORE',
            $texto
        );

        if($textoSemAcento !== false){
            $texto = $textoSemAcento;
        }

        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9]+/', '-', $texto);
        $texto = trim($texto, '-');

        return $texto !== ''
            ? $texto
            : 'imovel';
    }

    function dataValida($data){
        $objeto = DateTimeImmutable::createFromFormat('!Y-m-d', $data);

        return $objeto && $objeto->format('Y-m-d') === $data;
    }

    function formatarDataBr($data){
        return DateTimeImmutable::createFromFormat('!Y-m-d', $data)->format('d/m/Y');
    }

    function somarMeses($data, $meses){
        $dataInicial = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
        $diaOriginal = (int) $dataInicial->format('d');

        $mesDestino = $dataInicial
            ->modify('first day of this month')
            ->modify('+' . $meses . ' months');

        $ultimoDiaMes = (int) $mesDestino->format('t');
        $diaDestino = min($diaOriginal, $ultimoDiaMes);

        return $mesDestino->setDate(
            (int) $mesDestino->format('Y'),
            (int) $mesDestino->format('m'),
            $diaDestino
        );
    }

    function montarEndereco($logradouro, $numero, $complemento, $bairro, $cidade, $estado, $cep){
        $endereco = $logradouro . ', nº ' . $numero;

        if($complemento !== ''){
            $endereco .= ', ' . $complemento;
        }

        $endereco .= ', bairro ' . $bairro;
        $endereco .= ', ' . $cidade . '/' . $estado;

        if($cep !== ''){
            $endereco .= ', CEP ' . $cep;
        }

        return $endereco;
    }

    function executarPython($script, $arquivoJson){
        $raizProjeto = dirname(__DIR__, 2);

        if(PHP_OS_FAMILY === 'Windows'){
            $python = $raizProjeto .
                DIRECTORY_SEPARATOR . 'contratos' .
                DIRECTORY_SEPARATOR . 'venv' .
                DIRECTORY_SEPARATOR . 'Scripts' .
                DIRECTORY_SEPARATOR . 'python.exe';
        }

        else{
            $python = $raizProjeto .
                DIRECTORY_SEPARATOR . 'contratos' .
                DIRECTORY_SEPARATOR . 'venv' .
                DIRECTORY_SEPARATOR . 'bin' .
                DIRECTORY_SEPARATOR . 'python';
        }

        if(!is_file($python)){
            return [
                'sucesso' => false,
                'saida' => '',
                'erro' => 'O ambiente virtual do Python não foi encontrado em contratos/venv.'
            ];
        }

        $comando = [
            $python,
            $script,
            $arquivoJson
        ];

        $descritores = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];

        $processo = @proc_open(
            $comando,
            $descritores,
            $pipes,
            null,
            null,
            ['bypass_shell' => true]
        );

        if(!is_resource($processo)){
            return [
                'sucesso' => false,
                'saida' => '',
                'erro' => 'Não foi possível iniciar o Python.'
            ];
        }

        fclose($pipes[0]);

        $saida = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $erro = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $codigo = proc_close($processo);

        if($codigo === 0){
            return [
                'sucesso' => true,
                'saida' => trim($saida),
                'erro' => ''
            ];
        }

        return [
            'sucesso' => false,
            'saida' => trim($saida),
            'erro' => trim($erro)
        ];
    }

    $idUsuario = (int) $_SESSION['id_usuario'];
    $idAnuncio = campoPost('id_anuncio');

    if($idAnuncio === '' || !ctype_digit($idAnuncio)){
        voltarComErro('Anúncio inválido para emissão do contrato.');
    }

    $idAnuncio = (int) $idAnuncio;

    $sql = "
        SELECT
            a.id_anuncio,
            a.titulo,
            a.valor,
            a.estadia_minima_meses,
            a.status,
            t.nome AS tipo,
            e.logradouro,
            e.numero,
            e.complemento,
            e.bairro,
            e.cidade,
            e.estado,
            e.cep,
            u.nome AS locador_nome,
            u.cpf AS locador_cpf,
            u.rg AS locador_rg,
            u.nacionalidade AS locador_nacionalidade,
            u.estado_civil AS locador_estado_civil,
            u.profissao AS locador_profissao,
            u.email AS locador_email,
            u.telefone AS locador_telefone,
            cc.id_configuracao,
            cc.usar_contrato,
            cc.clausula_animais,
            cc.clausula_caucao,
            cc.clausula_agua_energia,
            cc.clausula_rescisao,
            cc.clausula_visitas,
            cc.clausula_manutencao,
            cc.clausula_multa,
            mc.arquivo AS arquivo_modelo
        FROM anuncio a
        INNER JOIN tipo_anuncio t
            ON t.id_tipo = a.id_tipo
        INNER JOIN endereco e
            ON e.id_endereco = a.id_endereco
        INNER JOIN usuario u
            ON u.id_usuario = a.id_usuario
        INNER JOIN configuracao_contrato cc
            ON cc.id_anuncio = a.id_anuncio
        INNER JOIN modelo_contrato mc
            ON mc.id_modelo = cc.id_modelo
        WHERE a.id_anuncio = ?
        AND a.id_usuario = ?
        AND a.status != 'REMOVIDO'
        AND cc.usar_contrato = TRUE
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idAnuncio, $idUsuario]);
    $anuncio = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$anuncio){
        voltarComErro('O contrato não está disponível para este anúncio.');
    }

    $dadosLocadorObrigatorios = [
        'locador_nome',
        'locador_cpf',
        'locador_rg',
        'locador_nacionalidade',
        'locador_estado_civil',
        'locador_profissao',
        'locador_email',
        'locador_telefone'
    ];

    foreach($dadosLocadorObrigatorios as $campo){
        if(trim((string) $anuncio[$campo]) === ''){
            voltarComErro(
                'Complete os seus dados cadastrais antes de emitir o contrato.'
            );
        }
    }

    $locadorLogradouro = campoPost('locador_logradouro');
    $locadorNumero = campoPost('locador_numero');
    $locadorComplemento = campoPost('locador_complemento');
    $locadorBairro = campoPost('locador_bairro');
    $locadorCidade = campoPost('locador_cidade');
    $locadorEstado = strtoupper(campoPost('locador_estado'));
    $locadorCep = campoPost('locador_cep');

    $locatarioNome = campoPost('locatario_nome');
    $locatarioNacionalidade = campoPost('locatario_nacionalidade');
    $locatarioEstadoCivil = campoPost('locatario_estado_civil');
    $locatarioProfissao = campoPost('locatario_profissao');
    $locatarioCpf = somenteDigitos(campoPost('locatario_cpf'));
    $locatarioRg = campoPost('locatario_rg');
    $locatarioTelefone = campoPost('locatario_telefone');
    $locatarioEmail = campoPost('locatario_email');
    $alunoInstituicao = campoPost('aluno_instituicao');
    $alunoMatricula = campoPost('aluno_matricula');

    $prazoLocacaoMeses = campoPost('prazo_locacao_meses');
    $dataInicio = campoPost('data_inicio');
    $diaVencimento = campoPost('dia_vencimento');
    $formaPagamento = campoPost('forma_pagamento');

    $cidadeAssinatura = campoPost('cidade_assinatura');
    $dataAssinatura = campoPost('data_assinatura');
    $numeroVias = campoPost('numero_vias');
    $foroComarca = campoPost('foro_comarca');
    $foroEstado = strtoupper(campoPost('foro_estado'));

    $testemunha1Nome = campoPost('testemunha1_nome');
    $testemunha1Cpf = somenteDigitos(campoPost('testemunha1_cpf'));
    $testemunha2Nome = campoPost('testemunha2_nome');
    $testemunha2Cpf = somenteDigitos(campoPost('testemunha2_cpf'));

    $camposObrigatorios = [
        $locadorLogradouro,
        $locadorNumero,
        $locadorBairro,
        $locadorCidade,
        $locadorEstado,
        $locatarioNome,
        $locatarioNacionalidade,
        $locatarioEstadoCivil,
        $locatarioProfissao,
        $locatarioCpf,
        $locatarioRg,
        $locatarioTelefone,
        $locatarioEmail,
        $alunoInstituicao,
        $alunoMatricula,
        $prazoLocacaoMeses,
        $dataInicio,
        $diaVencimento,
        $formaPagamento,
        $cidadeAssinatura,
        $dataAssinatura,
        $numeroVias,
        $foroComarca,
        $foroEstado,
        $testemunha1Nome,
        $testemunha1Cpf,
        $testemunha2Nome,
        $testemunha2Cpf
    ];

    foreach($camposObrigatorios as $campo){
        if($campo === ''){
            voltarComErro('Preencha todos os campos obrigatórios para emitir o contrato.');
        }
    }

    $estados = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF',
        'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
        'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS',
        'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
    ];

    if(!in_array($locadorEstado, $estados, true) || !in_array($foroEstado, $estados, true)){
        voltarComErro('Selecione estados válidos para o contrato.');
    }

    if(strlen($locatarioCpf) !== 11 || strlen($testemunha1Cpf) !== 11 || strlen($testemunha2Cpf) !== 11){
        voltarComErro('Informe CPFs válidos com 11 dígitos.');
    }

    if(!filter_var($locatarioEmail, FILTER_VALIDATE_EMAIL)){
        voltarComErro('Informe um e-mail válido para o inquilino.');
    }

    if(!ctype_digit($prazoLocacaoMeses) || (int) $prazoLocacaoMeses < 1){
        voltarComErro('Informe um prazo de locação válido.');
    }

    $prazoLocacaoMeses = (int) $prazoLocacaoMeses;
    $estadiaMinima = (int) $anuncio['estadia_minima_meses'];

    if($prazoLocacaoMeses < $estadiaMinima){
        voltarComErro(
            'O prazo da locação não pode ser inferior à estadia mínima de ' .
            $estadiaMinima .
            ($estadiaMinima === 1 ? ' mês.' : ' meses.')
        );
    }

    if(!ctype_digit($diaVencimento) || (int) $diaVencimento < 1 || (int) $diaVencimento > 31){
        voltarComErro('Informe um dia de vencimento entre 1 e 31.');
    }

    if(!ctype_digit($numeroVias) || (int) $numeroVias < 1){
        voltarComErro('Informe uma quantidade de vias válida.');
    }

    if(!dataValida($dataInicio) || !dataValida($dataAssinatura)){
        voltarComErro('Informe datas válidas para o contrato.');
    }

    $dataTermino = somarMeses($dataInicio, $prazoLocacaoMeses);

    $locadorEndereco = montarEndereco(
        $locadorLogradouro,
        $locadorNumero,
        $locadorComplemento,
        $locadorBairro,
        $locadorCidade,
        $locadorEstado,
        $locadorCep
    );

    $locatarioEndereco = montarEndereco(
        $anuncio['logradouro'],
        $anuncio['numero'],
        $anuncio['complemento'] ?? '',
        $anuncio['bairro'],
        $anuncio['cidade'],
        $anuncio['estado'],
        $anuncio['cep'] ?? ''
    );

    $sql = "
        SELECT
            texto,
            ordem
        FROM clausula_extra
        WHERE id_configuracao = ?
        ORDER BY ordem ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$anuncio['id_configuracao']]);
    $clausulasExtras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $textoClausulasExtras = '';

    if(!empty($clausulasExtras)){
        $linhasExtras = [];

        foreach($clausulasExtras as $clausula){
            $texto = trim((string) $clausula['texto']);

            if($texto !== ''){
                $linhasExtras[] = '• ' . $texto;
            }
        }

        $textoClausulasExtras = implode("\n", $linhasExtras);
    }

    $prazoLocacaoTexto = $prazoLocacaoMeses .
        ($prazoLocacaoMeses === 1 ? ' mês' : ' meses');

    $estadiaMinimaTexto = $estadiaMinima .
        ($estadiaMinima === 1 ? ' mês' : ' meses');

    $complementoImovel = trim((string) ($anuncio['complemento'] ?? ''));
    $cepImovel = trim((string) ($anuncio['cep'] ?? ''));

    $referencias = [
        '{{LOCADOR_NOME}}' => $anuncio['locador_nome'],
        '{{LOCADOR_NACIONALIDADE}}' => $anuncio['locador_nacionalidade'],
        '{{LOCADOR_ESTADO_CIVIL}}' => $anuncio['locador_estado_civil'],
        '{{LOCADOR_PROFISSAO}}' => $anuncio['locador_profissao'],
        '{{LOCADOR_CPF}}' => formatarCpf($anuncio['locador_cpf']),
        '{{LOCADOR_RG}}' => $anuncio['locador_rg'],
        '{{LOCADOR_ENDERECO}}' => $locadorEndereco,
        '{{LOCADOR_EMAIL}}' => $anuncio['locador_email'],
        '{{LOCADOR_TELEFONE}}' => $anuncio['locador_telefone'],

        '{{LOCATARIO_NOME}}' => $locatarioNome,
        '{{LOCATARIO_NACIONALIDADE}}' => $locatarioNacionalidade,
        '{{LOCATARIO_ESTADO_CIVIL}}' => $locatarioEstadoCivil,
        '{{LOCATARIO_PROFISSAO}}' => $locatarioProfissao,
        '{{LOCATARIO_CPF}}' => formatarCpf($locatarioCpf),
        '{{LOCATARIO_RG}}' => $locatarioRg,
        '{{LOCATARIO_ENDERECO}}' => $locatarioEndereco,
        '{{LOCATARIO_EMAIL}}' => $locatarioEmail,
        '{{LOCATARIO_TELEFONE}}' => $locatarioTelefone,
        '{{ALUNO_INSTITUICAO}}' => $alunoInstituicao,
        '{{ALUNO_MATRICULA}}' => $alunoMatricula,

        '{{ANUNCIO_TITULO}}' => $anuncio['titulo'],
        '{{TIPO_IMOVEL}}' => ucfirst(strtolower($anuncio['tipo'])),
        '{{LOGRADOURO}}' => $anuncio['logradouro'],
        '{{NUMERO}}' => $anuncio['numero'],
        '{{COMPLEMENTO}}' => $complementoImovel !== '' ? $complementoImovel : 'sem complemento',
        '{{BAIRRO}}' => $anuncio['bairro'],
        '{{CIDADE}}' => $anuncio['cidade'],
        '{{ESTADO}}' => $anuncio['estado'],
        '{{CEP}}' => $cepImovel !== '' ? $cepImovel : 'não informado',

        '{{PRAZO_LOCACAO}}' => $prazoLocacaoTexto,
        '{{DATA_INICIO}}' => formatarDataBr($dataInicio),
        '{{DATA_TERMINO}}' => $dataTermino->format('d/m/Y'),
        '{{ESTADIA_MINIMA_MESES}} meses' => $estadiaMinimaTexto,
        '{{VALOR_ALUGUEL}}' => number_format((float) $anuncio['valor'], 2, ',', '.'),
        '{{DIA_VENCIMENTO}}' => (int) $diaVencimento,
        '{{FORMA_PAGAMENTO}}' => $formaPagamento,

        '{{CLAUSULA_ANIMAIS}}' => $anuncio['clausula_animais']
            ? 'Animais: não são permitidos animais no imóvel.'
            : '',
        '{{CLAUSULA_CAUCAO}}' => $anuncio['clausula_caucao']
            ? 'Caução: será exigido pagamento de caução antecipado.'
            : '',
        '{{CLAUSULA_AGUA_ENERGIA}}' => $anuncio['clausula_agua_energia']
            ? 'Água e energia: os pagamentos ficam sob responsabilidade do(a) INQUILINO(A).'
            : '',
        '{{CLAUSULA_RESCISAO}}' => $anuncio['clausula_rescisao']
            ? 'Rescisão antecipada: poderá haver multa pela saída antecipada conforme estabelecido neste contrato.'
            : '',
        '{{CLAUSULA_VISITAS}}' => $anuncio['clausula_visitas']
            ? 'Visitas: é permitido receber visitantes no imóvel.'
            : '',
        '{{CLAUSULA_MANUTENCAO}}' => $anuncio['clausula_manutencao']
            ? 'Manutenção: conservação, danos causados pelo(a) INQUILINO(A) e pequenos reparos ficam sob sua responsabilidade.'
            : '',
        '{{CLAUSULA_MULTA}}' => $anuncio['clausula_multa']
            ? 'Multas: o(a) INQUILINO(A) estará sujeito(a) às multas previstas neste contrato em caso de descumprimento.'
            : '',
        '{{CLAUSULAS_EXTRAS}}' => $textoClausulasExtras,

        '{{FORO_COMARCA}}' => $foroComarca,
        '{{FORO_ESTADO}}' => $foroEstado,
        '{{NUMERO_VIAS}}' => (int) $numeroVias,
        '{{CIDADE_ASSINATURA}}' => $cidadeAssinatura,
        '{{DATA_ASSINATURA}}' => formatarDataBr($dataAssinatura),

        '{{TESTEMUNHA1_NOME}}' => $testemunha1Nome,
        '{{TESTEMUNHA1_CPF}}' => formatarCpf($testemunha1Cpf),
        '{{TESTEMUNHA2_NOME}}' => $testemunha2Nome,
        '{{TESTEMUNHA2_CPF}}' => formatarCpf($testemunha2Cpf),

        // O contrato já sai com o recibo preparado, mas ainda sem uma competência paga.
        '{{RECIBO_NUMERO}}' => '________',
        '{{RECIBO_COMPETENCIA}}' => '____/______',
        '{{RECIBO_VALOR}}' => '____________',
        '{{RECIBO_DATA_PAGAMENTO}}' => '____/____/______'
    ];

    $raizProjeto = dirname(__DIR__, 2);
    $arquivoModelo = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $anuncio['arquivo_modelo']);
    $caminhoModelo = $raizProjeto . DIRECTORY_SEPARATOR . ltrim($arquivoModelo, DIRECTORY_SEPARATOR);
    $scriptPython = $raizProjeto . DIRECTORY_SEPARATOR . 'contratos' . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'gerar_contrato.py';

    if(!is_file($caminhoModelo)){
        voltarComErro('O arquivo do modelo de contrato não foi encontrado.');
    }

    if(!is_file($scriptPython)){
        voltarComErro('O gerador Python do contrato não foi encontrado.');
    }

    $pastaGerados = $raizProjeto . DIRECTORY_SEPARATOR . 'contratos' . DIRECTORY_SEPARATOR . 'gerados' . DIRECTORY_SEPARATOR . $idAnuncio;
    $pastaTemporaria = $raizProjeto . DIRECTORY_SEPARATOR . 'contratos' . DIRECTORY_SEPARATOR . 'temp';

    if(!is_dir($pastaGerados) && !mkdir($pastaGerados, 0755, true) && !is_dir($pastaGerados)){
        voltarComErro('Não foi possível criar a pasta dos contratos gerados.');
    }

    if(!is_dir($pastaTemporaria) && !mkdir($pastaTemporaria, 0755, true) && !is_dir($pastaTemporaria)){
        voltarComErro('Não foi possível criar a pasta temporária dos contratos.');
    }

    try{
        $identificador = date('Ymd-His') . '-' . bin2hex(random_bytes(4));
    }
    catch(Exception $e){
        $identificador = date('Ymd-His') . '-' . uniqid();
    }

    $nomeBaseArquivo = 'contrato-unihub-' .
        normalizarNomeArquivo($anuncio['titulo']);

    $nomeArquivo = $nomeBaseArquivo . '.docx';
    $caminhoSaida = $pastaGerados . DIRECTORY_SEPARATOR . $nomeArquivo;

    $contadorArquivo = 2;

    while(is_file($caminhoSaida)){
        $nomeArquivo = $nomeBaseArquivo . '-' . $contadorArquivo . '.docx';
        $caminhoSaida = $pastaGerados . DIRECTORY_SEPARATOR . $nomeArquivo;
        $contadorArquivo++;
    }

    $arquivoJson = $pastaTemporaria . DIRECTORY_SEPARATOR . 'contrato-' . $identificador . '.json';

    $dadosPython = [
        'modelo' => $caminhoModelo,
        'saida' => $caminhoSaida,
        'valor_aluguel' => (string) $anuncio['valor'],
        'referencias' => $referencias,
        'remover_se_vazio' => [
            '{{CLAUSULA_ANIMAIS}}',
            '{{CLAUSULA_CAUCAO}}',
            '{{CLAUSULA_AGUA_ENERGIA}}',
            '{{CLAUSULA_RESCISAO}}',
            '{{CLAUSULA_VISITAS}}',
            '{{CLAUSULA_MANUTENCAO}}',
            '{{CLAUSULA_MULTA}}',
            '{{CLAUSULAS_EXTRAS}}'
        ]
    ];

    $json = json_encode(
        $dadosPython,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
    );

    if($json === false || file_put_contents($arquivoJson, $json) === false){
        voltarComErro('Não foi possível preparar os dados para gerar o contrato.');
    }

    $resultadoPython = executarPython($scriptPython, $arquivoJson);

    if(is_file($arquivoJson)){
        unlink($arquivoJson);
    }

    if(!$resultadoPython['sucesso'] || !is_file($caminhoSaida)){
        if(is_file($caminhoSaida)){
            unlink($caminhoSaida);
        }

        voltarComErro(
            'Não foi possível gerar o contrato. Verifique se o Python e a biblioteca python-docx estão instalados. ' .
            ($resultadoPython['erro'] !== '' ? 'Detalhes: ' . $resultadoPython['erro'] : '')
        );
    }

    $arquivoRelativo = 'contratos/gerados/' . $idAnuncio . '/' . $nomeArquivo;

    try{
        $sql = "
            INSERT INTO contrato_gerado (
                id_configuracao,
                arquivo,
                formato
            )
            VALUES (?, ?, 'DOCX')
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $anuncio['id_configuracao'],
            $arquivoRelativo
        ]);
    }
    catch(Exception $e){
        if(is_file($caminhoSaida)){
            unlink($caminhoSaida);
        }

        voltarComErro('O contrato foi gerado, mas não foi possível registrar a emissão no sistema.');
    }

    $_SESSION['sucesso'] = 'Contrato gerado com sucesso.';
    $_SESSION['contrato_gerado_url'] = '/UniHub/' . str_replace(
        DIRECTORY_SEPARATOR,
        '/',
        $arquivoRelativo
    );

    header('Location: /UniHub/pages/conta/minha-conta.php');
    exit;
?>