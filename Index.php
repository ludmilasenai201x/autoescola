<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

function read($pdo, $tabela) {
    $tabelasPermitidas = ["alunos", "instrutores"];

    if (!in_array($tabela, $tabelasPermitidas, true)) {
        http_response_code(400);
        echo json_encode(["erro" => "Tabela inválida"]);
        return;
    }

    $sql = "SELECT * FROM `$tabela`";
    $stmt = $pdo->query($sql);

    echo json_encode($stmt->fetchAll());
}

function create($pdo, $tabela) {
    $tabelasPermitidas = ["alunos", "instrutores"];

    if (!in_array($tabela, $tabelasPermitidas, true)) {
        http_response_code(400);
        echo json_encode(["erro" => "Tabela inválida"]);
        return;
    }

    $dados = json_decode(file_get_contents("php://input"), true);

    if (!is_array($dados)) {
        http_response_code(400);
        echo json_encode(["erro" => "JSON inválido"]);
        return;
    }

    $camposPermitidos = [
        "nome",
        "cpf",
        "telefone"
    ];

    $dados = array_intersect_key(
        $dados,
        array_flip($camposPermitidos)
    );

    if (empty($dados["nome"])) {
        http_response_code(400);
        echo json_encode(["erro" => "O nome é obrigatório"]);
        return;
    }

    $colunas = array_keys($dados);
    $nomes = implode(", ", $colunas);
    $marcadores = ":" . implode(", :", $colunas);

    $sql = "INSERT INTO `$tabela` ($nomes)
            VALUES ($marcadores)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($dados);

    http_response_code(201);

    echo json_encode([
        "mensagem" => "Cadastro realizado com sucesso",
        "id" => $pdo->lastInsertId()
    ]);
}

try {
    $metodo = $_SERVER["REQUEST_METHOD"];
    $recurso = $_GET["recurso"] ?? "";

    if ($metodo === "GET" &&
        in_array($recurso, ["alunos", "instrutores"], true)) {

        read($pdo, $recurso);

    } elseif ($metodo === "POST" &&
        in_array($recurso, ["alunos", "instrutores"], true)) {

        create($pdo, $recurso);

    } else {
        http_response_code(404);
        echo json_encode([
            "erro" => "Rota não encontrada"
        ]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "erro" => "Erro ao consultar ou cadastrar os dados"
    ]);
}