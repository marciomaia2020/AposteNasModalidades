<?php
// api.php - Versão final simplificada
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Responder a requisições OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuração do banco
$host = 'localhost';
$dbname = 'loterias_caixa';
$username = 'root';
$password = '';

// Log para debug
function logDebug($message) {
    file_put_contents('api_debug.log', date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

logDebug("=== Nova requisição ===");
logDebug("Método: " . $_SERVER['REQUEST_METHOD']);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logDebug("Conexão com banco OK");
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        // Receber dados
        $input = file_get_contents('php://input');
        logDebug("Dados recebidos: " . $input);
        
        $data = json_decode($input, true);
        
        if (!$data) {
            throw new Exception('JSON inválido');
        }
        
        $modalidade = $data['modalidade'] ?? '';
        $jogo = $data['jogo'] ?? [];
        
        logDebug("Modalidade: " . $modalidade);
        logDebug("Jogo: " . print_r($jogo, true));
        
        if (empty($modalidade) || empty($jogo['numbers'])) {
            throw new Exception('Modalidade ou números não informados');
        }
        
        $table = $modalidade . '_jogos';
        $numeros = implode(',', $jogo['numbers']);
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'localhost';
        
        logDebug("Tabela: " . $table);
        logDebug("Números: " . $numeros);
        logDebug("IP: " . $user_ip);
        
        // Verificar se tabela existe
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("Tabela $table não existe");
        }
        
        // Inserir dados baseado na modalidade
        switch($modalidade) {
            case 'timemania':
                $team = $jogo['team'] ?? '';
                if (empty($team)) {
                    throw new Exception('Time é obrigatório para Timemania');
                }
                $stmt = $pdo->prepare("INSERT INTO $table (numeros, time_coracao, usuario_ip) VALUES (?, ?, ?)");
                $stmt->execute([$numeros, $team, $user_ip]);
                break;
                
            case 'diadesorte':
                $month = $jogo['month'] ?? '';
                if (empty($month)) {
                    throw new Exception('Mês é obrigatório para Dia de Sorte');
                }
                $stmt = $pdo->prepare("INSERT INTO $table (numeros, mes_sorte, usuario_ip) VALUES (?, ?, ?)");
                $stmt->execute([$numeros, $month, $user_ip]);
                break;
                
            case 'maismilionaria':
                $trevos = $jogo['trevos'] ?? [];
                if (empty($trevos)) {
                    throw new Exception('Trevos são obrigatórios para + Milionária');
                }
                $trevos_str = implode(',', $trevos);
                $stmt = $pdo->prepare("INSERT INTO $table (numeros, trevos, usuario_ip) VALUES (?, ?, ?)");
                $stmt->execute([$numeros, $trevos_str, $user_ip]);
                break;
                
            case 'supersete':
                $numeros_supersete = implode('', $jogo['numbers']);
                $stmt = $pdo->prepare("INSERT INTO $table (numeros, usuario_ip) VALUES (?, ?)");
                $stmt->execute([$numeros_supersete, $user_ip]);
                break;
                
            case 'loteca':
                $stmt = $pdo->prepare("INSERT INTO $table (palpites, usuario_ip) VALUES (?, ?)");
                $stmt->execute([$numeros, $user_ip]);
                break;
                
            case 'federal':
                $numero = $jogo['numbers'][0] ?? '';
                if (empty($numero)) {
                    throw new Exception('Número é obrigatório para Federal');
                }
                $stmt = $pdo->prepare("INSERT INTO $table (numero, usuario_ip) VALUES (?, ?)");
                $stmt->execute([$numero, $user_ip]);
                break;
                
            default:
                // megasena, quina, lotofacil, lotomania, duplasena
                $stmt = $pdo->prepare("INSERT INTO $table (numeros, usuario_ip) VALUES (?, ?)");
                $stmt->execute([$numeros, $user_ip]);
                break;
        }
        
        $id = $pdo->lastInsertId();
        logDebug("Jogo inserido com ID: " . $id);
        
        echo json_encode([
            'success' => true,
            'id' => $id,
            'message' => 'Jogo salvo com sucesso!'
        ]);
        
    } elseif ($method === 'GET') {
        $modalidade = $_GET['modalidade'] ?? '';
        
        if (empty($modalidade)) {
            throw new Exception('Modalidade não informada');
        }
        
        $table = $modalidade . '_jogos';
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'localhost';
        
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE usuario_ip = ? ORDER BY data_criacao DESC");
        $stmt->execute([$user_ip]);
        $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logDebug("Encontrados " . count($jogos) . " jogos para " . $modalidade);
        
        echo json_encode([
            'success' => true,
            'jogos' => $jogos
        ]);
        
    } elseif ($method === 'DELETE') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        $modalidade = $data['modalidade'] ?? '';
        $id = $data['id'] ?? 0;
        
        if (empty($modalidade) || empty($id)) {
            throw new Exception('Modalidade e ID são obrigatórios');
        }
        
        $table = $modalidade . '_jogos';
        $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'localhost';
        
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ? AND usuario_ip = ?");
        $stmt->execute([$id, $user_ip]);
        
        echo json_encode([
            'success' => true,
            'affected' => $stmt->rowCount()
        ]);
    }
    
} catch (Exception $e) {
    logDebug("ERRO: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>