<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Log para debug
error_log("API chamada: " . $_SERVER['REQUEST_METHOD']);

// Configuração do banco - AJUSTE CONFORME SUA CONFIGURAÇÃO
$host = 'localhost';
$dbname = 'loterias_caixa';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log("Conexão com banco OK");
} catch(PDOException $e) {
    error_log("Erro de conexão: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(['error' => 'Erro de conexão: ' . $e->getMessage()]));
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Log dos dados recebidos
error_log("Dados recebidos: " . print_r($input, true));

function getUserIP() {
    return $_SERVER['REMOTE_ADDR'] ?? 'localhost';
}

$user_ip = getUserIP();

try {
    switch($method) {
        case 'OPTIONS':
            http_response_code(200);
            exit();
            
        case 'GET':
            $modalidade = $_GET['modalidade'] ?? '';
            if (empty($modalidade)) {
                throw new Exception('Modalidade não informada');
            }
            
            $table = $modalidade . '_jogos';
            $stmt = $pdo->prepare("SELECT * FROM $table WHERE usuario_ip = ? ORDER BY data_criacao DESC");
            $stmt->execute([$user_ip]);
            $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'jogos' => $jogos]);
            break;
            
        case 'POST':
            $modalidade = $input['modalidade'] ?? '';
            $jogo = $input['jogo'] ?? [];
            
            error_log("Salvando jogo: modalidade=$modalidade, jogo=" . print_r($jogo, true));
            
            if (empty($modalidade) || empty($jogo)) {
                throw new Exception('Dados incompletos');
            }
            
            $table = $modalidade . '_jogos';
            
            switch($modalidade) {
                case 'timemania':
                    $stmt = $pdo->prepare("INSERT INTO $table (numeros, time_coracao, usuario_ip) VALUES (?, ?, ?)");
                    $stmt->execute([
                        implode(',', $jogo['numbers']),
                        $jogo['team'],
                        $user_ip
                    ]);
                    break;
                    
                case 'diadesorte':
                    $stmt = $pdo->prepare("INSERT INTO $table (numeros, mes_sorte, usuario_ip) VALUES (?, ?, ?)");
                    $stmt->execute([
                        implode(',', $jogo['numbers']),
                        $jogo['month'],
                        $user_ip
                    ]);
                    break;
                    
                case 'maismilionaria':
                    $stmt = $pdo->prepare("INSERT INTO $table (numeros, trevos, usuario_ip) VALUES (?, ?, ?)");
                    $stmt->execute([
                        implode(',', $jogo['numbers']),
                        implode(',', $jogo['trevos']),
                        $user_ip
                    ]);
                    break;
                    
                case 'supersete':
                    $stmt = $pdo->prepare("INSERT INTO $table (numeros, usuario_ip) VALUES (?, ?)");
                    $stmt->execute([
                        implode('', $jogo['numbers']),
                        $user_ip
                    ]);
                    break;
                    
                case 'loteca':
                    $stmt = $pdo->prepare("INSERT INTO $table (palpites, usuario_ip) VALUES (?, ?)");
                    $stmt->execute([
                        implode(',', $jogo['numbers']),
                        $user_ip
                    ]);
                    break;
                    
                case 'federal':
                    $stmt = $pdo->prepare("INSERT INTO $table (numero, usuario_ip) VALUES (?, ?)");
                    $stmt->execute([
                        $jogo['numbers'][0],
                        $user_ip
                    ]);
                    break;
                    
                default:
                    // megasena, quina, lotofacil, lotomania, duplasena
                    $stmt = $pdo->prepare("INSERT INTO $table (numeros, usuario_ip) VALUES (?, ?)");
                    $stmt->execute([
                        implode(',', $jogo['numbers']),
                        $user_ip
                    ]);
                    break;
            }
            
            $id = $pdo->lastInsertId();
            error_log("Jogo salvo com ID: $id");
            
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Jogo salvo!']);
            break;
            
        case 'DELETE':
            $modalidade = $input['modalidade'] ?? '';
            $id = $input['id'] ?? 0;
            
            if (empty($modalidade) || empty($id)) {
                throw new Exception('Dados incompletos para deletar');
            }
            
            $table = $modalidade . '_jogos';
            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ? AND usuario_ip = ?");
            $stmt->execute([$id, $user_ip]);
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            throw new Exception('Método não permitido');
    }
    
} catch(Exception $e) {
    error_log("Erro: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>