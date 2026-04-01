<?php
session_start();
require_once '../config/database.php';

// Session aliases
if (isset($_SESSION['usuario_id'])) {
    $_SESSION['user_id'] = $_SESSION['usuario_id'];
    $_SESSION['user_name'] = $_SESSION['usuario_nombre'] ?? '';
    $_SESSION['user_role'] = $_SESSION['usuario_rol'] ?? 'vendedor';
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$action = $_REQUEST['action'] ?? '';
$uid = (int)$_SESSION['user_id'];

switch ($action) {
    case 'messages':
        header('Content-Type: application/json');
        $conv_id = (int)($_GET['conv_id'] ?? 0);
        if (!$conv_id) { echo json_encode(['error'=>'ID requerido']); exit; }

        // Check user is participant
        $check = $pdo->prepare("SELECT 1 FROM conversacion_participantes WHERE conversacion_id = :c AND usuario_id = :u");
        $check->execute(['c'=>$conv_id, 'u'=>$uid]);
        if (!$check->fetch()) { echo json_encode(['error'=>'No autorizado']); exit; }

        // Get messages with avatar
        $stmt = $pdo->prepare("
            SELECT m.id, m.conversacion_id, m.usuario_id, m.contenido, m.tipo, m.archivo_url, m.leido, m.eliminado, m.creado_en,
                   u.nombre as user_name, u.avatar
            FROM mensajes m
            JOIN usuarios u ON u.id = m.usuario_id
            WHERE m.conversacion_id = :c
            ORDER BY m.creado_en ASC
        ");
        $stmt->execute(['c'=>$conv_id]);
        $msgs = $stmt->fetchAll();

        // Get conversation info
        $conv = $pdo->prepare("SELECT nombre, tipo FROM conversaciones WHERE id = :c");
        $conv->execute(['c'=>$conv_id]);
        $convData = $conv->fetch();

        if ($convData && $convData['tipo'] === 'privada') {
            $parts = $pdo->prepare("SELECT u.nombre FROM conversacion_participantes cp JOIN usuarios u ON u.id = cp.usuario_id WHERE cp.conversacion_id = :c AND cp.usuario_id != :u LIMIT 1");
            $parts->execute(['c'=>$conv_id, 'u'=>$uid]);
            $other = $parts->fetch();
            $convName = $other ? $other['nombre'] : ($convData['nombre'] ?? 'Chat');
        } else {
            $convName = $convData['nombre'] ?? 'Grupo';
        }

        // Get participants for group info
        $partStmt = $pdo->prepare("SELECT u.id, u.nombre, u.avatar FROM conversacion_participantes cp JOIN usuarios u ON u.id = cp.usuario_id WHERE cp.conversacion_id = :c ORDER BY u.nombre");
        $partStmt->execute(['c'=>$conv_id]);
        $participantes = $partStmt->fetchAll(PDO::FETCH_ASSOC);

        $formatted = array_map(function($m) {
            $fecha = date('Y-m-d', strtotime($m['creado_en']));
            $hoy = date('Y-m-d');
            $ayer = date('Y-m-d', strtotime('-1 day'));
            if ($fecha === $hoy) $fechaLabel = 'Hoy';
            elseif ($fecha === $ayer) $fechaLabel = 'Ayer';
            else $fechaLabel = date('d/m/Y', strtotime($m['creado_en']));

            $result = [
                'id' => (int)$m['id'],
                'usuario_id' => (int)$m['usuario_id'],
                'user_name' => $m['user_name'],
                'avatar' => $m['avatar'],
                'mensaje' => $m['eliminado'] ? '' : $m['contenido'],
                'tipo' => $m['tipo'] ?: 'texto',
                'hora' => date('H:i', strtotime($m['creado_en'])),
                'fecha' => $fechaLabel,
                'leido' => (bool)$m['leido'],
                'eliminado' => (bool)$m['eliminado'],
            ];

            if ($m['archivo_url'] && !$m['eliminado']) {
                $result['archivo_url'] = $m['archivo_url'];
                $result['archivo_nombre'] = basename($m['archivo_url']);
            }

            return $result;
        }, $msgs);

        echo json_encode([
            'mensajes' => $formatted,
            'nombre' => $convName,
            'tipo' => $convData['tipo'] ?? 'privada',
            'participantes' => $participantes,
            'participantes_count' => count($participantes),
        ]);
        break;

    case 'send':
        header('Content-Type: application/json');
        $conv_id = (int)($_POST['conv_id'] ?? 0);
        $mensaje = trim($_POST['mensaje'] ?? '');
        $tipo = $_POST['tipo'] ?? 'texto';
        $archivoUrl = null;

        if (!$conv_id) { echo json_encode(['error'=>'Datos requeridos']); exit; }

        // Check participation
        $check = $pdo->prepare("SELECT 1 FROM conversacion_participantes WHERE conversacion_id = :c AND usuario_id = :u");
        $check->execute(['c'=>$conv_id, 'u'=>$uid]);
        if (!$check->fetch()) { echo json_encode(['error'=>'No autorizado']); exit; }

        // Validate type
        $allowedTypes = ['texto', 'imagen', 'audio', 'archivo'];
        if (!in_array($tipo, $allowedTypes)) $tipo = 'texto';

        // Handle file upload
        if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['archivo'];

            // Size limit 10MB
            if ($file['size'] > 10 * 1024 * 1024) {
                echo json_encode(['error' => 'Archivo demasiado grande (máx 10MB)']);
                exit;
            }

            // Allowed extensions
            $allowedExts = [
                'imagen' => ['jpg','jpeg','png','gif','webp','bmp','svg'],
                'audio'  => ['mp3','wav','ogg','m4a','aac','wma','flac','webm'],
                'archivo'=> ['pdf','doc','docx','xls','xlsx','zip','rar','txt','csv','ppt','pptx'],
            ];

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $validExts = $allowedExts[$tipo] ?? [];

            if (!in_array($ext, $validExts)) {
                echo json_encode(['error' => 'Tipo de archivo no permitido']);
                exit;
            }

            $uploadDir = dirname(__DIR__) . '/uploads/chat/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $newName = uniqid('chat_') . '.' . $ext;
            $destPath = $uploadDir . $newName;

            if (move_uploaded_file($file['tmp_name'], $destPath)) {
                $archivoUrl = 'uploads/chat/' . $newName;
            } else {
                echo json_encode(['error' => 'Error al subir archivo']);
                exit;
            }

            // For non-text types with no text message, use filename as content
            if (!$mensaje) $mensaje = $file['name'];
        } else {
            // Text-only message
            if (!$mensaje) { echo json_encode(['error'=>'Mensaje requerido']); exit; }
            $tipo = 'texto';
        }

        $stmt = $pdo->prepare("INSERT INTO mensajes (conversacion_id, usuario_id, contenido, tipo, archivo_url) VALUES (:c, :u, :m, :t, :a)");
        $stmt->execute([
            'c' => $conv_id,
            'u' => $uid,
            'm' => $mensaje,
            't' => $tipo,
            'a' => $archivoUrl,
        ]);
        $id = $pdo->lastInsertId();

        // Get user info
        $userStmt = $pdo->prepare("SELECT nombre, avatar FROM usuarios WHERE id = :id");
        $userStmt->execute(['id' => $uid]);
        $userData = $userStmt->fetch();

        $result = [
            'id' => (int)$id,
            'usuario_id' => $uid,
            'user_name' => $userData ? $userData['nombre'] : '',
            'avatar' => $userData ? $userData['avatar'] : null,
            'mensaje' => $mensaje,
            'tipo' => $tipo,
            'hora' => date('H:i'),
            'fecha' => 'Hoy',
        ];

        if ($archivoUrl) {
            $result['archivo_url'] = $archivoUrl;
            $result['archivo_nombre'] = basename($archivoUrl);
        }

        echo json_encode(['ok' => true, 'mensaje' => $result]);
        break;

    case 'read':
        header('Content-Type: application/json');
        $conv_id = (int)($_GET['conv_id'] ?? 0);
        if ($conv_id) {
            $pdo->prepare("UPDATE mensajes SET leido = 1 WHERE conversacion_id = :c AND usuario_id != :u AND leido = 0")
                ->execute(['c'=>$conv_id, 'u'=>$uid]);
        }
        echo json_encode(['ok' => true]);
        break;

    case 'new_conv':
        header('Content-Type: application/json');
        $target_id = (int)($_POST['usuario_id'] ?? 0);
        if (!$target_id || $target_id == $uid) {
            echo json_encode(['error' => 'Usuario inválido']);
            exit;
        }

        // Check if private conversation already exists
        $existing = $pdo->prepare("
            SELECT c.id FROM conversaciones c
            JOIN conversacion_participantes cp1 ON cp1.conversacion_id = c.id AND cp1.usuario_id = :u1
            JOIN conversacion_participantes cp2 ON cp2.conversacion_id = c.id AND cp2.usuario_id = :u2
            WHERE c.tipo = 'privada'
            LIMIT 1
        ");
        $existing->execute(['u1' => $uid, 'u2' => $target_id]);
        $existingConv = $existing->fetch();

        if ($existingConv) {
            echo json_encode(['ok' => true, 'conv_id' => (int)$existingConv['id']]);
        } else {
            $pdo->beginTransaction();
            try {
                $pdo->exec("INSERT INTO conversaciones (tipo) VALUES ('privada')");
                $newConvId = $pdo->lastInsertId();
                $ins = $pdo->prepare("INSERT INTO conversacion_participantes (conversacion_id, usuario_id) VALUES (:c, :u)");
                $ins->execute(['c' => $newConvId, 'u' => $uid]);
                $ins->execute(['c' => $newConvId, 'u' => $target_id]);
                $pdo->commit();
                echo json_encode(['ok' => true, 'conv_id' => (int)$newConvId]);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(['error' => 'Error al crear conversación']);
            }
        }
        break;

    case 'new_group':
        header('Content-Type: application/json');
        $nombre = trim($_POST['nombre'] ?? '');
        $usuarios = json_decode($_POST['usuarios'] ?? '[]', true);

        if (!$nombre || empty($usuarios)) {
            echo json_encode(['error' => 'Nombre y participantes requeridos']);
            exit;
        }

        // Sanitize
        $nombre = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $usuarios = array_map('intval', $usuarios);
        // Add creator
        if (!in_array($uid, $usuarios)) $usuarios[] = $uid;

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO conversaciones (tipo, nombre) VALUES ('grupo', :n)");
            $stmt->execute(['n' => $nombre]);
            $newConvId = $pdo->lastInsertId();

            $ins = $pdo->prepare("INSERT INTO conversacion_participantes (conversacion_id, usuario_id) VALUES (:c, :u)");
            foreach ($usuarios as $uId) {
                $ins->execute(['c' => $newConvId, 'u' => $uId]);
            }

            $pdo->commit();
            echo json_encode(['ok' => true, 'conv_id' => (int)$newConvId]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['error' => 'Error al crear grupo']);
        }
        break;

    case 'delete_msg':
        header('Content-Type: application/json');
        $msg_id = (int)($_POST['msg_id'] ?? 0);
        if (!$msg_id) { echo json_encode(['error' => 'ID requerido']); exit; }

        // Only the sender can delete
        $msgCheck = $pdo->prepare("SELECT id, archivo_url FROM mensajes WHERE id = :id AND usuario_id = :u");
        $msgCheck->execute(['id' => $msg_id, 'u' => $uid]);
        $msgData = $msgCheck->fetch();
        if (!$msgData) { echo json_encode(['error' => 'No autorizado']); exit; }

        // Mark as deleted
        $pdo->prepare("UPDATE mensajes SET eliminado = 1 WHERE id = :id")->execute(['id' => $msg_id]);

        // Delete attached file if exists
        if ($msgData['archivo_url']) {
            $filePath = dirname(__DIR__) . '/' . $msgData['archivo_url'];
            if (file_exists($filePath)) @unlink($filePath);
        }

        echo json_encode(['ok' => true]);
        break;

    default:
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Acción no válida']);
}
