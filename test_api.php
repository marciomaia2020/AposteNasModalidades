<?php
// test_api.php - Teste completo da API
echo "<h1>🔧 Teste Completo da API</h1>";

// Teste 1: Inserir via API
echo "<h2>📝 Teste 1: Inserir jogo via API</h2>";

$data = [
    'modalidade' => 'megasena',
    'jogo' => [
        'numbers' => ['07', '14', '21', '28', '35', '42'],
        'timestamp' => date('d/m/Y H:i:s')
    ]
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents('http://localhost/api.php', false, $context);

if ($result) {
    $response = json_decode($result, true);
    echo "<p>✅ <strong>Resposta da API:</strong></p>";
    echo "<pre>" . print_r($response, true) . "</pre>";
} else {
    echo "<p>❌ <strong>Erro ao chamar a API</strong></p>";
}

// Teste 2: Buscar via API
echo "<h2>📥 Teste 2: Buscar jogos via API</h2>";
$result = file_get_contents('http://localhost/api.php?modalidade=megasena');

if ($result) {
    $response = json_decode($result, true);
    echo "<p>✅ <strong>Jogos encontrados:</strong></p>";
    echo "<pre>" . print_r($response, true) . "</pre>";
} else {
    echo "<p>❌ <strong>Erro ao buscar jogos</strong></p>";
}
?>