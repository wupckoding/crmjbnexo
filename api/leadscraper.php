<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (isset($_SESSION['usuario_id'])) {
    $_SESSION['user_id'] = $_SESSION['usuario_id'];
    $_SESSION['user_role'] = $_SESSION['usuario_rol'] ?? 'vendedor';
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ─── Helpers: Web Scraping ───
function fetchUrl($url, $timeout = 15) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER     => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: es-ES,es;q=0.9,en;q=0.8',
        ],
    ]);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['html' => $html ?: '', 'code' => $code];
}

function extractEmails($html) {
    $text = strip_tags($html);
    preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,6}/', $text, $m);
    $emails = array_unique($m[0]);
    $bl = ['example.com','domain.com','email.com','test.com','sentry.io','w3.org','wixpress.com','googleapis.com','wordpress.org','schema.org','yoursite.com','tuempresa.com','yourdomain.com','yourcompany.com'];
    $out = [];
    foreach ($emails as $e) {
        $d = strtolower(substr($e, strpos($e, '@') + 1));
        $skip = false;
        foreach ($bl as $b) { if (strpos($d, $b) !== false) { $skip = true; break; } }
        if (preg_match('/^\d+x/', $e)) $skip = true;
        if (preg_match('/\.(?:png|jpg|gif|svg|webp|js|css)$/i', $e)) $skip = true;
        if (!$skip) $out[] = $e;
    }
    return array_values(array_slice($out, 0, 10));
}

function extractPhones($text) {
    $clean = strip_tags($text);
    preg_match_all('/(?:\+\d{1,3}[\s.\-]?)?\(?\d{2,4}\)?[\s.\-]?\d{3,4}[\s.\-]?\d{2,5}/', $clean, $m);
    $phones = [];
    foreach ($m[0] as $p) {
        $digits = preg_replace('/\D/', '', $p);
        if (strlen($digits) >= 8 && strlen($digits) <= 15) $phones[] = trim($p);
    }
    return array_values(array_unique(array_slice($phones, 0, 10)));
}

switch ($action) {

    // ─── Web Search (Brave Search) ───
    case 'search':
        $nicho    = trim($_GET['nicho'] ?? '');
        $location = trim($_GET['location'] ?? '');
        $page     = max(0, (int)($_GET['page'] ?? 0));
        if (!$nicho) { echo json_encode(['ok'=>false,'error'=>'Selecciona un nicho']); exit; }

        $q = $nicho . ($location ? ' en ' . $location : '') . ' contacto email teléfono';
        $braveUrl = 'https://search.brave.com/search?q=' . urlencode($q) . '&source=web';
        if ($page > 0) $braveUrl .= '&offset=' . $page;
        $resp = fetchUrl($braveUrl);
        if (!$resp['html']) { echo json_encode(['ok'=>false,'error'=>'Error de conexión. Intenta de nuevo.']); exit; }

        $results = [];
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $resp['html']);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $titleNodes = $xpath->query('//*[contains(@class,"search-snippet-title")]');
        $skipDomains = '/wikipedia\.org|youtube\.com|google\.com|brave\.com|yelp\.com|tripadvisor|facebook\.com|instagram\.com|twitter\.com|x\.com|tiktok\.com/i';

        for ($i = 0; $i < $titleNodes->length && count($results) < 20; $i++) {
            $titleDiv = $titleNodes->item($i);
            $title = trim($titleDiv->textContent);
            $aTag = $titleDiv->parentNode;
            $url = ($aTag && $aTag->nodeName === 'a') ? $aTag->getAttribute('href') : '';

            if (!$url || !$title) continue;
            if (preg_match($skipDomains, $url)) continue;

            // Description from sibling div.generic-snippet
            $snip = '';
            $resultContent = $aTag ? $aTag->parentNode : null;
            if ($resultContent) {
                foreach ($resultContent->childNodes as $child) {
                    if ($child->nodeType === XML_ELEMENT_NODE && strpos($child->getAttribute('class'), 'generic-snippet') !== false) {
                        $snip = trim($child->textContent);
                        break;
                    }
                }
            }

            $ph = extractPhones($snip);
            $em = extractEmails($snip);

            $results[] = [
                'nombre'    => html_entity_decode($title, ENT_QUOTES, 'UTF-8'),
                'sitio_web' => $url,
                'snippet'   => $snip,
                'telefono'  => $ph[0] ?? '',
                'email'     => $em[0] ?? '',
            ];
        }
        echo json_encode(['ok'=>true,'results'=>$results,'total'=>count($results),'query'=>$q,'page'=>$page]);
        break;

    // ─── Enrich URL (scrape website for contact data) ───
    case 'enrich_url':
        $url = trim($_GET['url'] ?? $_POST['url'] ?? '');
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) { echo json_encode(['ok'=>false,'error'=>'URL inválida']); exit; }

        $resp = fetchUrl($url, 12);
        if (!$resp['html']) { echo json_encode(['ok'=>false,'error'=>'No se pudo acceder al sitio']); exit; }

        $h = $resp['html'];
        $emails = extractEmails($h);
        $phones = extractPhones($h);

        // mailto: and tel: links
        preg_match_all('/href=["\']mailto:([^"\'?]+)/i', $h, $mm);
        if (!empty($mm[1])) $emails = array_values(array_unique(array_merge($emails, $mm[1])));
        preg_match_all('/href=["\']tel:([^"\']+)/i', $h, $tm);
        if (!empty($tm[1])) {
            foreach ($tm[1] as $t) {
                $cl = preg_replace('/\s+/', '', $t);
                if (strlen(preg_replace('/\D/', '', $cl)) >= 8) $phones[] = $cl;
            }
            $phones = array_values(array_unique($phones));
        }

        // Social links
        $social = [];
        preg_match_all('/https?:\/\/(?:www\.)?(?:linkedin\.com|instagram\.com|facebook\.com|twitter\.com|x\.com)\/[a-zA-Z0-9._\-\/]+/', $h, $sm);
        if (!empty($sm[0])) $social = array_values(array_unique(array_slice($sm[0], 0, 5)));

        // WhatsApp
        $whatsapp = '';
        if (preg_match('/wa\.me\/(\d+)/', $h, $wm)) $whatsapp = '+' . $wm[1];
        elseif (preg_match('/api\.whatsapp\.com\/send\?phone=(\d+)/', $h, $wm)) $whatsapp = '+' . $wm[1];

        // JSON-LD structured data
        $address = ''; $phone_ld = ''; $contactName = ''; $contactRole = ''; $orgName = '';
        preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $h, $ldm);
        foreach ($ldm[1] as $json) {
            $data = @json_decode($json, true);
            if (!$data) continue;
            if (!empty($data['telephone']) && !$phone_ld) $phone_ld = $data['telephone'];
            if (!empty($data['address']) && is_array($data['address'])) {
                $a = $data['address'];
                $address = implode(', ', array_filter([$a['streetAddress']??'',$a['addressLocality']??'',$a['addressRegion']??'',$a['addressCountry']??'']));
            }
            if (!empty($data['name']) && !$orgName) $orgName = $data['name'];
            // Extract founder/employee/contactPoint
            foreach (['founder','employee','author','creator'] as $personKey) {
                if (!empty($data[$personKey]) && !$contactName) {
                    $p = is_array($data[$personKey]) && isset($data[$personKey][0]) ? $data[$personKey][0] : $data[$personKey];
                    if (!empty($p['name'])) { $contactName = $p['name']; $contactRole = ucfirst($personKey); }
                }
            }
            if (!empty($data['contactPoint']) && !$contactName) {
                $cp = is_array($data['contactPoint']) && isset($data['contactPoint'][0]) ? $data['contactPoint'][0] : $data['contactPoint'];
                if (!empty($cp['name'])) $contactName = $cp['name'];
            }
        }
        if ($phone_ld && !in_array($phone_ld, $phones)) array_unshift($phones, $phone_ld);

        // Try to extract people names from meta tags / common patterns
        if (!$contactName) {
            if (preg_match('/<meta[^>]+name=["\']author["\'][^>]+content=["\']([^"\'>]+)/i', $h, $am)) $contactName = trim($am[1]);
        }

        echo json_encode([
            'ok'       => true,
            'emails'   => array_slice($emails, 0, 8),
            'phones'   => array_slice($phones, 0, 8),
            'social'   => $social,
            'whatsapp' => $whatsapp,
            'address'  => $address,
            'contact_name' => $contactName,
            'contact_role' => $contactRole,
            'org_name'     => $orgName,
        ]);
        break;

    // ─── Create / Save lead ───
    case 'save':
        $nombre_empresa  = trim($_POST['nombre_empresa'] ?? '');
        $nombre_contacto = trim($_POST['nombre_contacto'] ?? '');
        $cargo           = trim($_POST['cargo'] ?? '');
        $nicho           = trim($_POST['nicho'] ?? '');
        $email           = trim($_POST['email'] ?? '');
        $telefono        = trim($_POST['telefono'] ?? '');
        $whatsapp        = trim($_POST['whatsapp'] ?? '');
        $sitio_web       = trim($_POST['sitio_web'] ?? '');
        $direccion       = trim($_POST['direccion'] ?? '');
        $descripcion     = trim($_POST['descripcion'] ?? '');
        $rating          = !empty($_POST['rating']) ? (float)$_POST['rating'] : null;
        $place_id        = trim($_POST['google_place_id'] ?? '');

        if (!$nombre_empresa) {
            echo json_encode(['ok' => false, 'error' => 'Nombre de empresa requerido']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO leads (nombre_empresa, nombre_contacto, cargo, nicho, email, telefono, whatsapp, sitio_web, direccion, descripcion, rating, google_place_id) VALUES (:ne, :nc, :ca, :ni, :em, :te, :wa, :sw, :di, :de, :ra, :gp)");
        $stmt->execute([
            'ne' => $nombre_empresa, 'nc' => $nombre_contacto, 'ca' => $cargo,
            'ni' => $nicho, 'em' => $email, 'te' => $telefono, 'wa' => $whatsapp,
            'sw' => $sitio_web, 'di' => $direccion, 'de' => $descripcion,
            'ra' => $rating, 'gp' => $place_id
        ]);

        echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
        break;

    // ─── List saved leads ───
    case 'list':
        $rows = $pdo->query("SELECT l.*, u.nombre as asignado_nombre FROM leads l LEFT JOIN usuarios u ON l.asignado_a = u.id ORDER BY l.creado_en DESC")->fetchAll();
        echo json_encode(['ok' => true, 'leads' => $rows]);
        break;

    // ─── Update lead ───
    case 'update':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['ok' => false]); exit; }

        $fields = [];
        $params = ['id' => $id];
        $allowed = ['nombre_empresa','nombre_contacto','cargo','nicho','email','telefono','whatsapp','sitio_web','direccion','estado','descripcion'];
        foreach ($allowed as $f) {
            if (isset($_POST[$f])) {
                $fields[] = "$f = :$f";
                $params[$f] = trim($_POST[$f]);
            }
        }
        if (empty($fields)) { echo json_encode(['ok' => false, 'error' => 'Nada que actualizar']); exit; }

        $pdo->prepare("UPDATE leads SET " . implode(', ', $fields) . " WHERE id = :id")->execute($params);
        echo json_encode(['ok' => true]);
        break;

    // ─── Assign lead to employee ───
    case 'assign':
        $id = (int)($_POST['id'] ?? 0);
        $user_id = (int)($_POST['user_id'] ?? 0);
        if (!$id) { echo json_encode(['ok' => false]); exit; }

        $pdo->prepare("UPDATE leads SET asignado_a = :uid WHERE id = :id")->execute(['uid' => $user_id ?: null, 'id' => $id]);
        echo json_encode(['ok' => true]);
        break;

    // ─── Convert lead to pipeline client ───
    case 'to_pipeline':
        $id = (int)($_POST['id'] ?? 0);
        $asignado = (int)($_POST['asignado_a'] ?? 0);
        $etapa = trim($_POST['etapa'] ?? 'nuevo');
        if (!$id) { echo json_encode(['ok' => false]); exit; }

        $lead = $pdo->prepare("SELECT * FROM leads WHERE id = :id");
        $lead->execute(['id' => $id]);
        $l = $lead->fetch(PDO::FETCH_ASSOC);
        if (!$l) { echo json_encode(['ok' => false, 'error' => 'Lead no encontrado']); exit; }

        // Create client from lead
        $stmt = $pdo->prepare("INSERT INTO clientes (nombre, email, telefono, empresa, sitio_web, direccion, notas, estado, asignado_a, archivado) VALUES (:n, :e, :t, :emp, :sw, :dir, :not, :est, :asi, 0)");
        $notas = '';
        if ($l['cargo']) $notas .= "Cargo: " . $l['cargo'] . "\n";
        if ($l['whatsapp']) $notas .= "WhatsApp: " . $l['whatsapp'] . "\n";
        if ($l['descripcion']) $notas .= $l['descripcion'];

        $stmt->execute([
            'n'   => $l['nombre_contacto'] ?: $l['nombre_empresa'],
            'e'   => $l['email'],
            't'   => $l['telefono'] ?: $l['whatsapp'],
            'emp' => $l['nombre_empresa'],
            'sw'  => $l['sitio_web'],
            'dir' => $l['direccion'],
            'not' => trim($notas),
            'est' => $etapa,
            'asi' => $asignado ?: null
        ]);

        // Mark lead as converted
        $pdo->prepare("UPDATE leads SET estado = 'convertido', asignado_a = :uid WHERE id = :id")
            ->execute(['uid' => $asignado ?: null, 'id' => $id]);

        echo json_encode(['ok' => true, 'cliente_id' => (int)$pdo->lastInsertId()]);
        break;

    // ─── Import CSV ───
    case 'import_csv':
        if (empty($_FILES['csv_file']['tmp_name'])) {
            echo json_encode(['ok' => false, 'error' => 'Archivo CSV requerido']);
            exit;
        }
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');
        if (!$handle) {
            echo json_encode(['ok' => false, 'error' => 'No se pudo leer el archivo']);
            exit;
        }

        // Read header row
        $header = fgetcsv($handle, 0, ',');
        if (!$header) {
            fclose($handle);
            echo json_encode(['ok' => false, 'error' => 'Archivo vacío o formato inválido']);
            exit;
        }
        $header = array_map(function($h) { return strtolower(trim(str_replace(["\xEF\xBB\xBF", '"'], '', $h))); }, $header);

        // Map columns
        $colMap = [
            'nombre_empresa' => ['nombre_empresa','empresa','company','business','nombre','name','razón social','razon social'],
            'nombre_contacto'=> ['nombre_contacto','contacto','contact','contact_name','persona'],
            'cargo'          => ['cargo','position','role','puesto','título','titulo'],
            'nicho'          => ['nicho','niche','sector','industry','industria','rubro','categoría','categoria'],
            'email'          => ['email','correo','e-mail','mail','correo electrónico','correo electronico'],
            'telefono'       => ['telefono','teléfono','phone','tel','fono'],
            'whatsapp'       => ['whatsapp','wsp','wa','celular','móvil','movil'],
            'sitio_web'      => ['sitio_web','website','web','url','sitio','página','pagina'],
            'direccion'      => ['direccion','dirección','address','domicilio','ubicación','ubicacion'],
            'descripcion'    => ['descripcion','descripción','description','notas','notes','observaciones'],
        ];

        $indexes = [];
        foreach ($colMap as $field => $aliases) {
            $indexes[$field] = null;
            foreach ($aliases as $alias) {
                $idx = array_search($alias, $header);
                if ($idx !== false) { $indexes[$field] = $idx; break; }
            }
        }

        if ($indexes['nombre_empresa'] === null) {
            fclose($handle);
            echo json_encode(['ok' => false, 'error' => 'El CSV debe tener una columna "nombre_empresa" o "empresa". Columnas encontradas: ' . implode(', ', $header)]);
            exit;
        }

        $inserted = 0;
        $skipped = 0;
        $nicho = trim($_POST['nicho'] ?? '');

        $stmt = $pdo->prepare("INSERT INTO leads (nombre_empresa, nombre_contacto, cargo, nicho, email, telefono, whatsapp, sitio_web, direccion, descripcion) VALUES (:ne, :nc, :ca, :ni, :em, :te, :wa, :sw, :di, :de)");

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $ne = trim($row[$indexes['nombre_empresa']] ?? '');
            if (!$ne) { $skipped++; continue; }

            $vals = [];
            foreach ($indexes as $field => $idx) {
                $vals[$field] = ($idx !== null && isset($row[$idx])) ? trim($row[$idx]) : '';
            }
            if ($nicho && !$vals['nicho']) $vals['nicho'] = $nicho;

            try {
                $stmt->execute([
                    'ne' => $vals['nombre_empresa'], 'nc' => $vals['nombre_contacto'],
                    'ca' => $vals['cargo'], 'ni' => $vals['nicho'],
                    'em' => $vals['email'], 'te' => $vals['telefono'],
                    'wa' => $vals['whatsapp'], 'sw' => $vals['sitio_web'],
                    'di' => $vals['direccion'], 'de' => $vals['descripcion']
                ]);
                $inserted++;
            } catch (Exception $e) {
                $skipped++;
            }
        }
        fclose($handle);

        echo json_encode(['ok' => true, 'inserted' => $inserted, 'skipped' => $skipped]);
        break;

    // ─── Check duplicates against clientes ───
    case 'check_duplicates':
        $checks = json_decode(file_get_contents('php://input'), true);
        if (!$checks || !is_array($checks)) { echo json_encode(['ok'=>true,'duplicates'=>[]]); exit; }

        $duplicates = [];
        foreach ($checks as $c) {
            $email   = trim($c['email'] ?? '');
            $phone   = trim($c['telefono'] ?? '');
            $website = trim($c['sitio_web'] ?? '');
            $empresa = trim($c['nombre'] ?? '');
            $idx     = $c['idx'] ?? 0;

            $conditions = [];
            $params = [];
            if ($email) { $conditions[] = "email = :em"; $params['em'] = $email; }
            if ($phone) {
                $phoneDigits = preg_replace('/\D/', '', $phone);
                if (strlen($phoneDigits) >= 7) {
                    $conditions[] = "REPLACE(REPLACE(REPLACE(REPLACE(telefono,' ',''),'-',''),'+',''),'.','') LIKE :ph";
                    $params['ph'] = '%' . substr($phoneDigits, -7) . '%';
                }
            }
            if ($website) {
                $domain = preg_replace('/^https?:\/\/(?:www\.)?/', '', strtolower($website));
                $domain = rtrim($domain, '/');
                if ($domain) { $conditions[] = "sitio_web LIKE :sw"; $params['sw'] = '%' . $domain . '%'; }
            }
            if ($empresa) { $conditions[] = "(empresa LIKE :emp OR nombre LIKE :emp)"; $params['emp'] = '%' . $empresa . '%'; }

            if (empty($conditions)) continue;

            $sql = "SELECT id, nombre, email, empresa FROM clientes WHERE (" . implode(' OR ', $conditions) . ") LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $match = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($match) {
                $duplicates[] = ['idx' => $idx, 'cliente_id' => (int)$match['id'], 'cliente_nombre' => $match['nombre'], 'cliente_email' => $match['email'] ?? '', 'cliente_empresa' => $match['empresa'] ?? ''];
            }
        }
        echo json_encode(['ok'=>true,'duplicates'=>$duplicates]);
        break;

    // ─── Delete lead ───
    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['ok' => false]); exit; }
        $pdo->prepare("DELETE FROM leads WHERE id = :id")->execute(['id' => $id]);
        echo json_encode(['ok' => true]);
        break;

    // ─── Assign from search: create client directly ───
    case 'assign_from_search':
        $nombre  = trim($_POST['nombre'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        $sitio_web = trim($_POST['sitio_web'] ?? '');
        $whatsapp = trim($_POST['whatsapp'] ?? '');
        $direccion = trim($_POST['direccion'] ?? '');
        $nicho    = trim($_POST['nicho'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $contactName = trim($_POST['nombre_contacto'] ?? '');
        $asignado_a = (int)($_POST['asignado_a'] ?? 0);
        $etapa = trim($_POST['etapa'] ?? 'nuevo');

        if (!$nombre) { echo json_encode(['ok'=>false,'error'=>'Nombre requerido']); exit; }
        if (!$asignado_a) { echo json_encode(['ok'=>false,'error'=>'Selecciona un funcionario']); exit; }

        $notas = '';
        if ($nicho) $notas .= "Nicho: $nicho\n";
        if ($whatsapp) $notas .= "WhatsApp: $whatsapp\n";
        if ($descripcion) $notas .= $descripcion;

        $stmt = $pdo->prepare("INSERT INTO clientes (nombre, email, telefono, empresa, sitio_web, direccion, notas, estado, asignado_a, archivado) VALUES (:n, :e, :t, :emp, :sw, :dir, :not, :est, :asi, 0)");
        $stmt->execute([
            'n'   => $contactName ?: $nombre,
            'e'   => $email,
            't'   => $telefono ?: $whatsapp,
            'emp' => $nombre,
            'sw'  => $sitio_web,
            'dir' => $direccion,
            'not' => trim($notas),
            'est' => $etapa,
            'asi' => $asignado_a
        ]);

        echo json_encode(['ok'=>true,'cliente_id'=>(int)$pdo->lastInsertId()]);
        break;

    // ─── Bulk assign from search ───
    case 'bulk_assign_from_search':
        $items = json_decode($_POST['items'] ?? '[]', true);
        $asignado_a = (int)($_POST['asignado_a'] ?? 0);
        $etapa = trim($_POST['etapa'] ?? 'nuevo');
        $nicho = trim($_POST['nicho'] ?? '');

        if (!$items || !is_array($items)) { echo json_encode(['ok'=>false,'error'=>'Sin datos']); exit; }
        if (!$asignado_a) { echo json_encode(['ok'=>false,'error'=>'Selecciona un funcionario']); exit; }

        $created = 0;
        $stmt = $pdo->prepare("INSERT INTO clientes (nombre, email, telefono, empresa, sitio_web, direccion, notas, estado, asignado_a, archivado) VALUES (:n, :e, :t, :emp, :sw, :dir, :not, :est, :asi, 0)");

        foreach ($items as $item) {
            $nombre = trim($item['nombre'] ?? '');
            if (!$nombre) continue;
            $notas = '';
            if ($nicho) $notas .= "Nicho: $nicho\n";
            if (!empty($item['whatsapp'])) $notas .= "WhatsApp: " . $item['whatsapp'] . "\n";
            if (!empty($item['descripcion'])) $notas .= $item['descripcion'];

            try {
                $stmt->execute([
                    'n'   => trim($item['nombre_contacto'] ?? '') ?: $nombre,
                    'e'   => trim($item['email'] ?? ''),
                    't'   => trim($item['telefono'] ?? '') ?: trim($item['whatsapp'] ?? ''),
                    'emp' => $nombre,
                    'sw'  => trim($item['sitio_web'] ?? ''),
                    'dir' => trim($item['direccion'] ?? ''),
                    'not' => trim($notas),
                    'est' => $etapa,
                    'asi' => $asignado_a
                ]);
                $created++;
            } catch (Exception $e) { /* skip duplicates */ }
        }

        echo json_encode(['ok'=>true,'created'=>$created]);
        break;

    default:
        echo json_encode(['error' => 'Acción no válida']);
}
