<?php
// termene_leads_api.php (Hybrid v1 + v2)
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/* ---------- helpers ---------- */

if (!function_exists('json_response')) {
    function json_response($a) { echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }
}

function current_user_id(){
    if (isset($_SESSION['user_id'])) return (int)$_SESSION['user_id'];
    if (isset($GLOBALS['user_id'])) return (int)$GLOBALS['user_id'];
    return 1;
}

$RAW_JSON = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $in = file_get_contents('php://input');
    if ($in && ($tmp = json_decode($in, true)) && is_array($tmp)) $RAW_JSON = $tmp;
}
function req($key, $default=null){
    global $RAW_JSON;
    if (isset($_POST[$key])) return $_POST[$key];
    if (isset($_GET[$key]))  return $_GET[$key];
    if (is_array($RAW_JSON) && array_key_exists($key, $RAW_JSON)) return $RAW_JSON[$key];
    return $default;
}

function normalizePhone($phone) {
    if (!$phone) return null;
    $phone = preg_replace('/\s+/', '', (string)$phone);
    if (strpos($phone, '+4') !== 0) $phone = '+4' . ltrim($phone, '+');
    return $phone;
}

/* ---------- Termene API v1 (for searches by name/CAEN) ---------- */

function termene_v1_search(array $params){
    global $TERMENE;
    
    if (empty($TERMENE['user']) || empty($TERMENE['pass'])) {
        throw new Exception('Termene credentials missing');
    }
    
    $url = 'https://termene.ro/api/dateFirmaSumar.php?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $TERMENE['user'] . ':' . $TERMENE['pass'],
        CURLOPT_TIMEOUT => (int)($TERMENE['timeout'] ?? 20),
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($body === false) throw new Exception('cURL error: ' . $curlError);
    if ($httpCode >= 400) throw new Exception("Termene HTTP $httpCode: $body");
    
    $data = json_decode($body, true);
    if ($data === null) throw new Exception('Invalid JSON from Termene');
    
    return $data;
}

/* ---------- Termene API v2 (for CUI detail lookups) ---------- */

function termene_v2_detail($cui){
    global $TERMENE;
    
    if (empty($TERMENE['api_key'])) throw new Exception('Termene API key missing');
    if (empty($TERMENE['user']) || empty($TERMENE['pass'])) throw new Exception('Termene credentials missing');
    
    $payload = [
        'cui' => (int)$cui,
        'schemaKey' => $TERMENE['api_key']
    ];
    
    $url = rtrim($TERMENE['base_url'], '/');
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => (int)($TERMENE['timeout'] ?? 20),
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_USERPWD => $TERMENE['user'] . ':' . $TERMENE['pass'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
    ]);

    $body = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($body === false) throw new Exception('cURL error: ' . $curlError);
    if ($httpCode >= 400) throw new Exception("Termene HTTP $httpCode: $body");

    $data = json_decode($body, true);
    if ($data === null) throw new Exception('Invalid JSON from Termene: ' . $body);
    
    return $data;
}

/** Map v1 search results to standard format */
function map_v1_search_result(array $item){
    return [
        'Date Generale' => [
            'cui' => (string)($item['cui'] ?? ''),
            'nume' => $item['nume'] ?? '',
            'judet' => $item['judet'] ?? '',
            'localitate' => $item['localitate'] ?? '',
            'cod_caen' => $item['cod_caen'] ?? '',
            'telefon' => null, // v1 search doesn't include phone
            'cifra_de_afaceri_neta' => is_array($item['cifra_afaceri'] ?? null) 
                ? (int)end($item['cifra_afaceri']) 
                : null,
        ]
    ];
}

/** Map company data from v2 response */
function map_company(array $obj){
    $g = $obj['Date Generale'] ?? $obj['dateGenerale'] ?? $obj;

    $cui   = $g['cui'] ?? $g['cif'] ?? $g['CUI'] ?? null;
    $name  = $g['nume'] ?? $g['denumire'] ?? $g['firma'] ?? null;
    $phone = $g['telefon'] ?? $g['phone'] ?? null;

    $judet      = $g['judet'] ?? $g['county'] ?? null;
    $localitate = $g['localitate'] ?? $g['locality'] ?? $g['oras'] ?? null;

    $caen = $g['cod_caen'] ?? $g['CAEN'] ?? null;
    $tip  = $g['tip_activitate'] ?? $g['activitate'] ?? null;

    $ca = null;
    foreach (['cifra_de_afaceri_neta','cifra_afaceri','cifraAfaceri','turnover'] as $k)
        if (isset($g[$k])) { $ca = (int)$g[$k]; break; }

    $stat_fisc = $g['statut_fiscal'] ?? $g['status'] ?? null;
    $stat_tva  = $g['statut_TVA'] ?? $g['tva'] ?? null;

    $founded = null;
    if (isset($g['vechime_firma']['data'])) $founded = date('Y-m-d', strtotime($g['vechime_firma']['data']));
    if (isset($g['data_infiintare']))       $founded = date('Y-m-d', strtotime($g['data_infiintare']));

    $last_upd = null;
    if (isset($g['ultima_actualizare'])) $last_upd = date('Y-m-d H:i:s', strtotime($g['ultima_actualizare']));

    return [
        'cui'           => $cui,
        'name'          => $name,
        'phone'         => normalizePhone($phone),
        'email'         => $g['email'] ?? null,
        'judet'         => $judet,
        'localitate'    => $localitate,
        'cod_caen'      => $caen,
        'tip_activ'     => $tip,
        'cifra_afaceri' => $ca,
        'statut_fiscal' => $stat_fisc,
        'statut_tva'    => $stat_tva,
        'founded_at'    => $founded,
        'last_update'   => $last_upd,
        'raw'           => $obj,
    ];
}

/* ---------- router ---------- */

$UID    = current_user_id();
$action = (string)req('action', '');

/* ---------- actions ---------- */
try {
    // Search companies using v1 API
    if ($action === 'search') {
        $params = [];
        
        $nume = trim((string)req('nume',''));
        $caen = preg_replace('/\D+/', '', (string)req('cod_caen',''));
        
        // V1 API only supports 'nume' parameter for search
        // We'll search by name and filter results by CAEN if needed
        if ($nume !== '') {
            $params['nume'] = $nume;
        } elseif ($caen !== '') {
            // Search for companies with this CAEN - use a common word to get results
            $params['nume'] = 'SRL';
        } else {
            json_response(['success'=>false, 'error'=>'Vă rugăm să completați numele firmei']);
        }
        
        $raw = termene_v1_search($params);
        
        // Ensure we have an array
        if (!is_array($raw)) $raw = [$raw];
        
        // Convert v1 format to our standard format
        $list = array_map('map_v1_search_result', $raw);
        
        // Filter by CAEN if specified
        if ($caen !== '') {
            $list = array_values(array_filter($list, function($o) use ($caen) {
                $g = $o['Date Generale'] ?? [];
                return (string)($g['cod_caen'] ?? '') === $caen;
            }));
        }
        
        // Filter by judet if specified
        $judet = trim((string)req('judet',''));
        if ($judet !== '') {
            $list = array_values(array_filter($list, function($o) use ($judet) {
                $g = $o['Date Generale'] ?? [];
                return stripos((string)($g['judet'] ?? ''), $judet) !== false;
            }));
        }
        
        // Filter by cifra afaceri if specified
        $cifra_min = (int)req('cifra_min', 0);
        if ($cifra_min > 0) {
            $list = array_values(array_filter($list, function($o) use ($cifra_min) {
                $g = $o['Date Generale'] ?? [];
                $ca = (int)($g['cifra_de_afaceri_neta'] ?? 0);
                return $ca >= $cifra_min;
            }));
        }
        
        // Filter active only
        if ((int)req('only_active',0) === 1) {
            // Note: v1 search doesn't return status, so we'll fetch details for active check
            // This is expensive, so we'll skip this filter for v1 searches
            // User should use v2 search once they get the schema key
        }
        
        // Enrich with phone numbers if requested (using v2 detail API)
        if ((int)req('only_phone',0) === 1) {
            $out = [];
            $cap = min(count($list), 50); // Limit to 50 to avoid too many API calls
            
            foreach (array_slice($list, 0, $cap) as $o) {
                $g = $o['Date Generale'] ?? [];
                $cui = preg_replace('/\D+/', '', (string)($g['cui'] ?? ''));
                
                if ($cui) {
                    try {
                        // Get full details from v2 API
                        $detail = termene_v2_detail($cui);
                        $dg = $detail['Date Generale'] ?? $detail['dateGenerale'] ?? $detail;
                        $tel = $dg['telefon'] ?? null;
                        
                        if ($tel) {
                            // Use the enriched data
                            $out[] = $detail;
                        }
                    } catch (Exception $e) {
                        // Skip companies we can't get details for
                        error_log('Error fetching details for CUI ' . $cui . ': ' . $e->getMessage());
                    }
                }
            }
            $list = $out;
        }
        
        json_response(['success'=>true, 'data'=>$list, 'note'=>'Using API v1 for search. Contact Termene for v2 search schema.']);
    }

    // Import lead by CUI using v2 detail API
    if ($action === 'import_by_cui') {
        $cui = preg_replace('/\D+/', '', (string)req('cui',''));
        if ($cui === '') json_response(['success'=>false,'error'=>'CUI missing']);

        $raw = termene_v2_detail($cui);
        $m = map_company($raw);
        if (!$m['cui'] || !$m['name']) throw new Exception('Date companie invalide');

        $stmt = $pdo->prepare("INSERT INTO termene_leads
            (user_id, cui, name, phone, email, judet, localitate, cod_caen, tip_activitate, cifra_afaceri, statut_fiscal, statut_tva, founded_at, last_termene_update, raw_json)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                name=VALUES(name), phone=VALUES(phone), email=VALUES(email),
                judet=VALUES(judet), localitate=VALUES(localitate),
                cod_caen=VALUES(cod_caen), tip_activitate=VALUES(tip_activitate),
                cifra_afaceri=VALUES(cifra_afaceri), statut_fiscal=VALUES(statut_fiscal),
                statut_tva=VALUES(statut_tva), founded_at=VALUES(founded_at),
                last_termene_update=VALUES(last_termene_update), raw_json=VALUES(raw_json)");
        $stmt->execute([
            current_user_id(),
            $m['cui'], $m['name'], $m['phone'], $m['email'],
            $m['judet'], $m['localitate'], $m['cod_caen'], $m['tip_activ'],
            $m['cifra_afaceri'], $m['statut_fiscal'], $m['statut_tva'],
            $m['founded_at'], $m['last_update'],
            json_encode($m['raw'], JSON_UNESCAPED_UNICODE)
        ]);

        json_response(['success'=>true, 'cui'=>$m['cui']]);
    }

    // Import bulk
    if ($action === 'import_bulk') {
        $cuis = req('cuis', '[]');
        if (is_string($cuis)) $cuis = json_decode($cuis, true);
        if (!is_array($cuis)) $cuis = [];

        $imported = 0; $errors = [];
        foreach ($cuis as $cuiRaw) {
            $cui = preg_replace('/\D+/', '', (string)$cuiRaw);
            if (!$cui) continue;
            try {
                $raw = termene_v2_detail($cui);
                $m = map_company($raw);
                if (!$m['cui'] || !$m['name']) throw new Exception('Date companie invalide');

                $stmt = $pdo->prepare("INSERT INTO termene_leads
                    (user_id, cui, name, phone, email, judet, localitate, cod_caen, tip_activitate, cifra_afaceri, statut_fiscal, statut_tva, founded_at, last_termene_update, raw_json)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                    ON DUPLICATE KEY UPDATE
                        name=VALUES(name), phone=VALUES(phone), email=VALUES(email),
                        judet=VALUES(judet), localitate=VALUES(localitate),
                        cod_caen=VALUES(cod_caen), tip_activitate=VALUES(tip_activitate),
                        cifra_afaceri=VALUES(cifra_afaceri), statut_fiscal=VALUES(statut_fiscal),
                        statut_tva=VALUES(statut_tva), founded_at=VALUES(founded_at),
                        last_termene_update=VALUES(last_termene_update), raw_json=VALUES(raw_json)");
                $stmt->execute([
                    current_user_id(),
                    $m['cui'], $m['name'], $m['phone'], $m['email'],
                    $m['judet'], $m['localitate'], $m['cod_caen'], $m['tip_activ'],
                    $m['cifra_afaceri'], $m['statut_fiscal'], $m['statut_tva'],
                    $m['founded_at'], $m['last_update'],
                    json_encode($m['raw'], JSON_UNESCAPED_UNICODE)
                ]);
                $imported++;
            } catch (Throwable $e) {
                $errors[] = ['cui'=>$cui, 'error'=>$e->getMessage()];
            }
        }
        json_response(['success'=>true, 'imported'=>$imported, 'errors'=>$errors]);
    }

    // List imported
    if ($action === 'list_imported') {
        $stmt = $pdo->prepare("SELECT created_at, cui, name, phone, judet, localitate, cod_caen
                               FROM termene_leads
                               WHERE user_id=? ORDER BY created_at DESC LIMIT 300");
        $stmt->execute([ current_user_id() ]);
        json_response(['success'=>true, 'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    json_response(['success'=>false, 'error'=>'Unknown action']);
} catch (Throwable $e) {
    json_response(['success'=>false, 'error'=>$e->getMessage()]);
}