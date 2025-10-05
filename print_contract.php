<?php
/*******************************************************
 * print_contract.php
 * Layout modeled on "Contract - Belial Group.pdf"
 * All articles (ART.1-4 and beyond) loaded from contract_templates
 * Shows discount from associated offer if exists
 *******************************************************/

declare(strict_types=1);
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php'; // provides $pdo (PDO)

// ---------- Input ----------
$contractId  = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['contract_id']) ? (int)$_GET['contract_id'] : 0);
$templateIdQ = isset($_GET['template_id']) ? (int)$_GET['template_id'] : null;
$contactIdQ  = isset($_GET['contact_id']) ? (int)$_GET['contact_id'] : 0; // NEW: Contact selector

if ($contractId <= 0) {
    http_response_code(400);
    echo "Missing or invalid contract id.";
    exit;
}

// ---------- Fetch contract + parties ----------
$sql = "
    SELECT
        ct.id, ct.user_id, ct.company_cui, ct.offer_id,
        ct.contract_number, ct.contract_date, ct.object, ct.special_clauses,
        ct.total_value, ct.duration_months, ct.template_id,

        c.Name  AS client_name,
        c.Adress AS client_address,
        c.Reg   AS client_reg,
        c.CUI   AS client_cui,

        u.name  AS seller_contact_name,
        u.company_name AS seller_company_name,
        u.company_cif  AS seller_reg,
        u.cui          AS seller_cui,
        u.billing_address AS seller_address,
        u.iban         AS seller_iban,
        u.banc_name    AS seller_bank,
        u.company_site, u.contact_email, u.telefon AS seller_phone,
        u.logo AS seller_logo
    FROM contracts ct
    INNER JOIN companies c ON c.CUI = ct.company_cui
    INNER JOIN users u     ON u.id  = ct.user_id
    WHERE ct.id = :id
    LIMIT 1
";
$stm = $pdo->prepare($sql);
$stm->execute([':id' => $contractId]);
$contract = $stm->fetch();

if (!$contract) {
    http_response_code(404);
    echo "Contract not found.";
    exit;
}

// ---------- Get discount from associated offer if exists ----------
$discount_amount = 0;
$discount_type = 'percent';
$discount_value = 0;

if (!empty($contract['offer_id'])) {
    try {
        $stm = $pdo->prepare("
            SELECT discount_amount, discount_type, discount_value 
            FROM offers 
            WHERE id = :offer_id 
            LIMIT 1
        ");
        $stm->execute([':offer_id' => (int)$contract['offer_id']]);
        $offer = $stm->fetch();
        
        if ($offer) {
            $discount_amount = (float)($offer['discount_amount'] ?? 0);
            $discount_type = $offer['discount_type'] ?? 'percent';
            $discount_value = (float)($offer['discount_value'] ?? 0);
        }
    } catch (PDOException $e) {
        // Silently fail, discount will remain 0
    }
}

// ---------- Optional client representative ----------
$clientContact = null;
try {
    $stm = $pdo->prepare("
        SELECT name, phone, email 
        FROM contacts 
        WHERE companie = :cui 
        ORDER BY role DESC, id ASC 
        LIMIT 1
    ");
    $stm->execute([':cui' => (int)$contract['company_cui']]);
    $clientContact = $stm->fetch() ?: null;
} catch (PDOException $e) {
    $clientContact = null;
}

// ---------- Template list (for UI switching, optional) ----------
$templates = [];
try {
    $stm = $pdo->prepare("
        SELECT id, user_id, title, updated_at
        FROM contract_templates
        WHERE user_id IN (0, :uid)
        ORDER BY user_id DESC, updated_at DESC, id DESC
    ");
    $stm->execute([':uid' => (int)$contract['user_id']]);
    $templates = $stm->fetchAll();
} catch (PDOException $e) {
    $templates = [];
}

$effectiveTemplateId = $templateIdQ ?? ($contract['template_id'] ? (int)$contract['template_id'] : null);

// ---------- Load all sections from DB template (including ART.1–ART.4) ----------
function loadTemplateData(PDO $pdo, ?int $templateId, int $userId): ?array {
    if (!empty($templateId)) {
        $stm = $pdo->prepare("SELECT data FROM contract_templates WHERE id = :tid LIMIT 1");
        $stm->execute([':tid' => $templateId]);
        $row = $stm->fetch();
        if ($row && !empty($row['data'])) {
            $dec = json_decode($row['data'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) return $dec;
        }
    }
    $stm = $pdo->prepare("SELECT data FROM contract_templates WHERE user_id = :uid ORDER BY updated_at DESC, id DESC LIMIT 1");
    $stm->execute([':uid' => $userId]);
    $row = $stm->fetch();
    if ($row && !empty($row['data'])) {
        $dec = json_decode($row['data'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) return $dec;
    }
    $stm = $pdo->query("SELECT data FROM contract_templates WHERE user_id = 0 ORDER BY updated_at DESC, id DESC LIMIT 1");
    $row = $stm->fetch();
    if ($row && !empty($row['data'])) {
        $dec = json_decode($row['data'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($dec)) return $dec;
    }
    return null;
}
$templateSections = loadTemplateData($pdo, $effectiveTemplateId, (int)$contract['user_id']) ?? [];

// ---------- Helpers ----------
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money(?float $v): string { $v = (float)($v ?? 0); return number_format($v, 2, ',', '.'); }
$contractDate = $contract['contract_date'] ? date('d/m/Y', strtotime($contract['contract_date'])) : '';
$duration_months = $contract['duration_months'] ? $contract['duration_months'] : 30;
$totalValue   = (float)($contract['total_value'] ?? 0);

// Calculate final total after discount
$finalTotal = $totalValue - $discount_value;
if ($finalTotal < 0) $finalTotal = 0;

// Build object lines for ART.2 replacement
$objectLines = [];
if (!empty($contract['object'])) {
    $raw = preg_split('/\r\n|\r|\n/', (string)$contract['object']);
    foreach ($raw as $line) {
        $t = trim($line);
        if ($t !== '') $objectLines[] = $t;
    }
}
if (!$objectLines) {
    $objectLines = ['Servicii conform ofertei anexate.'];
}
$art2_object_html = '<ol style="margin:0;padding-left:18px;">';
foreach ($objectLines as $li) {
    $art2_object_html .= '<li>'.h($li).'</li>';
}
$art2_object_html .= '</ol>';
// ---------- NEW: Get ALL contacts for dropdown ----------
$allContacts = [];
try {
    $stm = $pdo->prepare("SELECT id, name, phone, email, role FROM contacts WHERE companie = :cui ORDER BY role ASC, id ASC");
    $stm->execute([':cui' => (int)$contract['company_cui']]);
    $allContacts = $stm->fetchAll() ?: [];
} catch (PDOException $e) {
    $allContacts = [];
}

// ---------- Select contact (from dropdown or auto-select director/manager) ----------
$clientContact = null;
if ($contactIdQ > 0 && count($allContacts) > 0) {
    // User selected a specific contact
    foreach ($allContacts as $c) {
        if ((int)$c['id'] === $contactIdQ) {
            $clientContact = $c;
            break;
        }
    }
}
// If no contact selected, auto-select first (highest role due to ORDER BY role DESC)
if (!$clientContact && count($allContacts) > 0) {
    $clientContact = $allContacts[0]; // First = highest role (director/manager)
}

// ---------- Merge vars for placeholders ----------
$vars = [
  '{{CONTRACT.NUMBER}}' => (string)($contract['contract_number'] ?? ''),
  '{{DOC.DATE}}'   => date("d/m/Y"),
  '{{CONTRACT.DATE}}'   => $contractDate,
  '{{CONTRACT.TIME}}'   => (string)$duration_months,
  '{{PROJECT.NAME}}'   => (string)$contract['special_clauses'],
  '{{CONTRACT.TOTAL}}'  => $finalTotal > 0 ? money($finalTotal) . ' Lei' : '0.00 Lei',
  '{{CONTRACT.SUBTOTAL}}' => $totalValue > 0 ? money($totalValue) . ' Lei' : '0.00 Lei',
  '{{CONTRACT.DISCOUNT}}' => $discount_value > 0 ? money($discount_value) . ' Lei' : '',

  '{{PRESTATOR.NAME}}'    => (string)($contract['seller_company_name'] ?? ''),
  '{{PRESTATOR.ADDRESS}}' => (string)($contract['seller_address'] ?? ''),
  '{{PRESTATOR.REG}}'     => (string)($contract['seller_reg'] ?? ''),
  '{{PRESTATOR.CUI}}'     => (string)($contract['seller_cui'] ?? ''),
  '{{PRESTATOR.IBAN}}'    => (string)($contract['seller_iban'] ?? ''),
  '{{PRESTATOR.BANK}}'    => (string)($contract['seller_bank'] ?? ''),
  '{{PRESTATOR.REP}}'     => (string)($contract['seller_contact_name'] ?? ''),

  '{{BENEFICIAR.NAME}}'    => (string)($contract['client_name'] ?? ''),
  '{{BENEFICIAR.ADDRESS}}' => (string)($contract['client_address'] ?? ''),
  '{{BENEFICIAR.REG}}'     => (string)($contract['client_reg'] ?? ''),
  '{{BENEFICIAR.CUI}}'     => (string)($contract['client_cui'] ?? ''),
  '{{BENEFICIAR.REP}}'     => (string)($clientContact['name'] ?? ''),
  
  '{{OBJECT.LIST}}'        => $art2_object_html,
];

function tpl_merge(string $html, array $map): string {
    return strtr($html, $map);
}

// ---------- Build all sections from template ----------
$sections_html = '';
if (!empty($templateSections)) {
    ob_start();
    foreach ($templateSections as $key => $sec) {
        $t = isset($sec['title']) ? trim((string)$sec['title']) : '';
        $b = isset($sec['body'])  ? (string)$sec['body'] : '';
        if($key == 0) {
			if ($t !== '') echo '<center><h1>'.tpl_merge(h($t), $vars).'</h3></center>';
			echo '<div><center>'.tpl_merge($b, $vars).'</center><br></div>';
		}else{
			if ($t !== '') echo '<h3 class="sec-title">'.tpl_merge(h($t), $vars).'</h3>';
			echo '<div class="sec-body">'.tpl_merge($b, $vars).'</div>';
		}
    }
    $sections_html = ob_get_clean();
}
$template = null;




// ---------- HTML ----------
?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <title><?= $contract['client_name']; ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=0.7">
  <style>
    :root{--text:#111;--muted:#666;--line:#e5e7eb;--brand:#0f172a;--bg:#fff;--accent:#0b5cff}
    *{box-sizing:border-box}
    html,body{margin:0;padding:0;background:var(--bg);color:var(--text); text-indent: 0pt;text-align: justify;
      font:12px/1.55 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Ubuntu,"Helvetica Neue",Arial}
    .toolbar{max-width:900px;margin:12px auto 0;padding:0 24px 6px;display:flex;gap:8px;justify-content:space-between;align-items:center}
    .btn{border:1px solid var(--line);background:#f9fafb;padding:8px 12px;border-radius:8px;cursor:pointer;font-size:13px}
    .select{border:1px solid var(--line);padding:8px 12px;border-radius:8px;font-size:13px;background:#fff}

    .page{max-width:1200px;margin:12px auto 24px;padding:24px;background:#fff}
    .header{display:flex;align-items:center;justify-content:space-between;gap:16px;border-bottom:2px solid var(--line);padding-bottom:16px}
    .logo{width:256px;background:#f8f8f8;border:1px solid var(--line);border-radius:6px;display:flex;align-items:center;justify-content:center;overflow:hidden}
    .logo img{width:100%;height:100%;object-fit:contain}
    .seller{text-align:right}
    .seller .name{font-weight:800;font-size:16px;color:var(--brand)}
    .muted{color:var(--muted)}

    .title{margin-top:8px;text-align:center}
    .title h1{margin:0;font-size:18px;letter-spacing:.2px}
    .subtitle{margin-top:4px;text-align:center;font-weight:700}

    .meta{display:grid;grid-template-columns:1fr 1fr;gap:16px;padding:16px 0;border-bottom:1px solid var(--line)}
    .card{border:1px solid var(--line);border-radius:8px;padding:12px}
    .card h3{margin:0 0 6px;font-size:14px;color:var(--brand)}
    .kv{margin:2px 0}.kv b{display:inline-block;min-width:160px}

    .sections{margin-top:18px}
    .sec-title{margin:16px 0 6px;font-size:15px;color:var(--brand);position:relative;font-weight:800}

    .sec-body{border:1px solid var(--line);border-radius:8px;padding:12px;background:#fff}
    /* Emphasize first four articles visually */
    .sections h3.sec-title:nth-of-type(-n+1)+.sec-body{border-left:3px solid var(--accent);background:#fafbff}

    .discount-badge{margin-top:8px;padding:8px;background:#fef9e7;border:1px solid #f9e79f;border-radius:6px;font-size:13px}
    .discount-badge strong{color:#856404}

    .sign{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:28px}
    .sign .box{border:1px dashed var(--line);border-radius:8px;padding:16px;min-height:120px}
    .footer{margin-top:14px;font-size:12px;color:var(--muted);text-align:center}

    @media print{
      .toolbar{display:none}
      .page{margin:0;padding:0}
      .logo{border:none;background:transparent}
      a[href]:after{content:""}
    }
  </style>
</head>
<body>

<div class="toolbar">
  <div>
    <button class="btn" onclick="window.print()">Printează</button>
    <a class="btn" href="?id=<?= (int)$contractId; ?>">Resetează</a>
  </div>
  <div>
    <?php if (count($allContacts) > 0): ?>
    <!-- NEW: Contact Selector -->
    <form method="get" action="" style="display:inline-flex;gap:8px;align-items:center;">
      <input type="hidden" name="id" value="<?= (int)$contractId; ?>">
      <?php if ($templateIdQ): ?>
        <input type="hidden" name="template_id" value="<?= (int)$templateIdQ; ?>">
      <?php endif; ?>
      <label for="contact_id" class="muted">Contact:</label>
      <select id="contact_id" name="contact_id" class="select" onchange="this.form.submit()">
        <option value="0">— Auto (Director/Manager) —</option>
        <?php foreach ($allContacts as $c): ?>
          <option value="<?= (int)$c['id']; ?>" <?= ($contactIdQ === (int)$c['id'] ? 'selected' : ''); ?>>
            <?= h($c['name']); ?><?= $c['role'] ? ' (Rol: '.$c['role'].')' : ''; ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
    <?php endif; ?>
    
    <!-- Template Selector -->
    <form method="get" action="" style="display:inline-flex;gap:8px;align-items:center;">
      <input type="hidden" name="id" value="<?= (int)$contractId; ?>">
      <?php if ($contactIdQ): ?>
        <input type="hidden" name="contact_id" value="<?= (int)$contactIdQ; ?>">
      <?php endif; ?>
      <label for="template_id" class="muted">Template:</label>
      <select id="template_id" name="template_id" class="select" onchange="this.form.submit()">
        <option value="">— automat (user > global) —</option>
        <?php foreach ($templates as $tpl): ?>
          <option value="<?= (int)$tpl['id']; ?>" <?= (($effectiveTemplateId === (int)$tpl['id']) ? 'selected' : ''); ?>>
            <?= h(($tpl['user_id'] ? 'Personal' : 'Global').': '.$tpl['title'].' (#'.$tpl['id'].')'); ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
</div>

<div class="page">

  <div class="header">
    <div class="logo">
      <?php if (!empty($contract['seller_logo'])): ?>
        <img src="<?= h($contract['seller_logo']); ?>" alt="Logo">
      <?php else: ?>
        <span class="muted">LOGO</span>
      <?php endif; ?>
    </div>
    <div class="seller">
      <div class="name"><?= h($contract['seller_company_name'] ?: ''); ?></div>
      <div class="muted"><?= nl2br(h($contract['seller_address'] ?: '')); ?></div>
      <div class="muted">CUI: <?= h($contract['seller_cui'] ?: '-'); ?> | Reg: <?= h($contract['seller_reg'] ?: '-'); ?></div>
      <div class="muted">Tel: <?= h($contract['seller_phone'] ?: '-'); ?> | Email: <?= h($contract['contact_email'] ?: '-'); ?></div>
      <?php if (!empty($contract['company_site'])): ?>
        <div class="muted"><?= h($contract['company_site']); ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="sections">
      <?php if ($discount_value > 0): ?>
	   <div class="sec-body">
        <div class="discount-badge">
          <strong>Discount aplicat:</strong> 
          <?php if ($discount_type === 'percent'): ?>
            <?= number_format($discount_amount, 2); ?>% 
            (<?= money($discount_value); ?> Lei)
          <?php else: ?>
            <?= money($discount_value); ?> Lei
          <?php endif; ?>
        </div>
        </div>
      <?php endif; ?>

    <?= $sections_html ?>
  </div>
<!--
  <div class="sign">
    <div class="box">
      <strong>PRESTATOR</strong><br>
      <div class="muted"><?= h($contract['seller_company_name'] ?: ''); ?></div>
      <div style="height:64px;"></div>
      <div class="muted">Semnătură și ștampilă</div>
    </div>
    <div class="box">
      <strong>BENEFICIAR</strong><br>
      <div class="muted"><?= h($contract['client_name'] ?: ''); ?></div>
      <div style="height:64px;"></div>
      <div class="muted">Semnătură și ștampilă</div>
    </div>
  </div>

  <div class="footer">
    Document emis electronic de <?= h($contract['seller_company_name'] ?: ''); ?>.
  </div>
-->
</div>
</body>
</html>