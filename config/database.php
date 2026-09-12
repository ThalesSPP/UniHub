<?php 
    $host = '127.0.0.1';
    $port = '3306';
    $dbname = 'UniHub';
    $usuarioBanco = 'root';
    $senhaBanco = 'SenhaGrande';

    try {
        $pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
            $usuarioBanco,
            $senhaBanco
        );

        $pdo->setAttribute(
            PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION
        );

        $pdo->setAttribute(
            PDO::ATTR_DEFAULT_FETCH_MODE,
            PDO::FETCH_ASSOC
        );
    } 
    
    catch (PDOException $erro){
        die('Erro ao conectar com o banco de dados.'. $erro->getMessage());
    }
?>