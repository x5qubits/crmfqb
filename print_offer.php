<?php
declare(strict_types=1);
mb_internal_encoding('UTF-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$offerId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['offer_id']) ? (int)$_GET['offer_id'] : 0);
$templateIdQ = isset($_GET['template_id']) ? (int)$_GET['template_id'] : null;

if ($offerId <= 0) {
    http_response_code(400);
    echo "Missing or invalid offer id.";
    exit;
}

$sqlOffer = "
    SELECT 
        o.id, o.user_id, o.company_cui, o.offer_number, o.offer_date, o.total_value, o.details, 
        o.template_id, o.discount_type, o.discount_amount, o.discount_value,
        c.Name AS client_name, c.Adress AS client_address, c.CUI AS client_cui,
        u.name AS seller_contact_name,
        u.company_name AS seller_company_name, 
        u.company_cif AS seller_reg, 
        u.cui AS seller_cui,
        u.billing_address AS seller_address,
        u.company_site, u.contact_email, u.telefon AS seller_phone,
        u.logo AS seller_logo
    FROM offers o
    INNER JOIN companies c ON c.CUI = o.company_cui
    INNER JOIN users u ON u.id = o.user_id
    WHERE o.id = :id
    LIMIT 1
";
$stm = $pdo->prepare($sqlOffer);
$stm->execute([':id' => $offerId]);
$offer = $stm->fetch();

if (!$offer) {
    http_response_code(404);
    echo "Offer not found.";
    exit;
}

$sqlItems = "SELECT id, description, quantity, unit_price, subtotal FROM offer_items WHERE offer_id = :oid ORDER BY id ASC";
$stm = $pdo->prepare($sqlItems);
$stm->execute([':oid' => $offerId]);
$items = $stm->fetchAll();

$clientContact = null;
try {
    $stm = $pdo->prepare("SELECT name, phone, email FROM contacts WHERE companie = :cui ORDER BY role DESC, id ASC LIMIT 1");
    $stm->execute([':cui' => (int)$offer['company_cui']]);
    $clientContact = $stm->fetch() ?: null;
} catch (PDOException $e) {
    $clientContact = null;
}

$templates = [];
try {
    $stm = $pdo->prepare("SELECT id, user_id, title, updated_at FROM offer_templates WHERE user_id IN (0, :uid) ORDER BY user_id DESC, updated_at DESC, id DESC");
    $stm->execute([':uid' => (int)$offer['user_id']]);
    $templates = $stm->fetchAll();
} catch (PDOException $e) {
    $templates = [];
}

$effectiveTemplateId = $templateIdQ ?? ($offer['template_id'] ? (int)$offer['template_id'] : null);

function loadTemplateData(PDO $pdo, ?int $templateId, int $userId): ?array {
    if (!empty($templateId)) {
        $stm = $pdo->prepare("SELECT data FROM offer_templates WHERE id = :tid LIMIT 1");
        $stm->execute([':tid' => $templateId]);
        $row = $stm->fetch();
        if ($row && !empty($row['data'])) {
            $decoded = json_decode($row['data'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) return $decoded;
        }
    }
    $stm = $pdo->prepare("SELECT data FROM offer_templates WHERE user_id = :uid ORDER BY updated_at DESC, id DESC LIMIT 1");
    $stm->execute([':uid' => $userId]);
    $row = $stm->fetch();
    if ($row && !empty($row['data'])) {
        $decoded = json_decode($row['data'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) return $decoded;
    }
    $stm = $pdo->query("SELECT data FROM offer_templates WHERE user_id = 0 ORDER BY updated_at DESC, id DESC LIMIT 1");
    $row = $stm->fetch();
    if ($row && !empty($row['data'])) {
        $decoded = json_decode($row['data'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) return $decoded;
    }
    return null;
}

$sectionsArray = loadTemplateData($pdo, $effectiveTemplateId, (int)$offer['user_id']) ?? [];

$sections = '';
if (!empty($sectionsArray)) {
    ob_start();
    foreach ($sectionsArray as $sec) {
        $title = isset($sec['title']) ? trim((string)$sec['title']) : '';
        $body  = isset($sec['body'])  ? (string)$sec['body'] : '';
        if ($title !== '') echo '<h3 class="sec-title">'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'</h3>';
        echo '<div class="sec-body">'.$body.'</div>';
    }
    $sections = ob_get_clean();
} else {
    $sections = '<h3 class="sec-title">Notă</h3><div class="sec-body"><p>Această ofertă este valabilă 30 de zile de la data emiterii.</p></div>';
}

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money(float $v): string { return number_format($v, 2, ',', '.'); }

$offerDate = $offer['offer_date'] ? date('d.m.Y', strtotime($offer['offer_date'])) : '';

// Calculate subtotal from items
$subtotal = 0;
foreach ($items as $it) {
    $subtotal += (float)($it['subtotal'] ?? ((int)$it['quantity'] * (float)$it['unit_price']));
}

// Get discount info
$discountType = $offer['discount_type'] ?? 'percent';
$discountAmount = (float)($offer['discount_amount'] ?? 0);
$discountValue = (float)($offer['discount_value'] ?? 0);

// Calculate final total
$finalTotal = max(0, $subtotal - $discountValue);
?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <title>Ofertă <?= h($offer['offer_number']); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root { --text:#111; --muted:#666; --line:#e5e7eb; --brand:#0f172a; --bg:#fff; }
    * { box-sizing:border-box; }
    html,body { margin:0; padding:0; background:var(--bg); color:var(--text); font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Ubuntu,"Helvetica Neue",Arial; }
    .toolbar { max-width:900px; margin:12px auto 0; padding:0 24px 6px; display:flex; gap:8px; justify-content:space-between; align-items:center; }
    .toolbar .left { display:flex; gap:8px; align-items:center; }
    .toolbar .right { display:flex; gap:8px; }
    .btn { border:1px solid var(--line); background:#f9fafb; padding:8px 12px; border-radius:8px; cursor:pointer; font-size:13px; text-decoration:none; color:var(--text); }
    .btn:active { transform:translateY(1px); }
    .select { border:1px solid var(--line); padding:8px 12px; border-radius:8px; font-size:13px; background:#fff; }
    .page { max-width:900px; margin:12px auto 24px; padding:24px; background:#fff; }
    .header { display:flex; align-items:center; justify-content:space-between; gap:16px; border-bottom:2px solid var(--line); padding-bottom:16px; }
    .logo { width:256px;  object-fit:contain; background:#f8f8f8; border:1px solid var(--line); border-radius:6px; display:flex; align-items:center; justify-content:center; overflow:hidden; }
    .logo img { width:100%; height:100%; object-fit:contain; }
    .seller { text-align:right; }
    .seller .name { font-weight:700; font-size:16px; color:var(--brand); }
    .muted { color:var(--muted); }
    .meta { display:grid; grid-template-columns:1fr 1fr; gap:16px; padding:16px 0; border-bottom:1px solid var(--line); }
    .card { border:1px solid var(--line); border-radius:8px; padding:12px; }
    .card h3 { margin:0 0 6px; font-size:14px; color:var(--brand); }
    .kv { margin:2px 0; }
    .kv b { display:inline-block; width:120px; }
    .items { margin-top:20px; border:1px solid var(--line); border-radius:8px; overflow:hidden; }
    table { width:100%; border-collapse:collapse; }
    thead th { background:#f6f7f9; font-weight:600; border-bottom:1px solid var(--line); padding:10px; text-align:left; font-size:13px; }
    tbody td { border-bottom:1px solid var(--line); padding:10px; vertical-align:top; }
    tfoot td { padding:10px; font-weight:600; }
    .right { text-align:right; }
    .total { font-size:16px; font-weight:700; }
    .sections { margin-top:24px; }
    .sec-title { margin:16px 0 6px; font-size:15px; color:var(--brand); }
    .sec-body p { margin:8px 0; }
    .legal { margin-top:18px; font-size:12px; color:var(--muted); border-top:1px solid var(--line); padding-top:12px; }
    .footer { margin-top:10px; font-size:12px; color:var(--muted); text-align:center; }

    @media print {
      .toolbar { display:none; }
      .page { margin:0; padding:0; }
      .logo { border:none; background:transparent; }
      a[href]:after { content:""; }
    }
  </style>
</head>
<body>

<div class="toolbar">
  <div class="left">
    <button class="btn" onclick="window.print()">Printează</button>
    <a class="btn" href="?id=<?= (int)$offerId; ?>">Resetează selecția</a>
  </div>
  <div class="right">
    <form method="get" action="" style="display:flex; gap:8px; align-items:center;">
      <input type="hidden" name="id" value="<?= (int)$offerId; ?>">
      <label for="template_id" class="muted">Template ofertă:</label>
      <select id="template_id" name="template_id" class="select" onchange="this.form.submit()">
        <option value="">— automat (user > global) —</option>
        <?php foreach ($templates as $tpl): ?>
          <option value="<?= (int)$tpl['id']; ?>" <?= ($effectiveTemplateId === (int)$tpl['id'] ? 'selected' : ''); ?>>
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
      <?php if (!empty($offer['seller_logo'])): ?>
        <img src="<?= h($offer['seller_logo']); ?>" alt="Logo">
      <?php else: ?>
        <span class="muted">LOGO</span>
      <?php endif; ?>
    </div>
    <div class="seller">
      <div class="name"><?= h($offer['seller_company_name'] ?: ''); ?></div>
      <div class="muted"><?= nl2br(h($offer['seller_address'] ?: '')); ?></div>
      <div class="muted">CUI: <?= h($offer['seller_cui'] ?: '-'); ?> | Reg: <?= h($offer['seller_reg'] ?: '-'); ?></div>
      <div class="muted">Tel: <?= h($offer['seller_phone'] ?: '-'); ?> | Email: <?= h($offer['contact_email'] ?: '-'); ?></div>
      <?php if (!empty($offer['company_site'])): ?>
        <div class="muted"><?= h($offer['company_site']); ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="meta">
    <div class="card">
      <h3>Ofertă</h3>
      <div class="kv"><b>Număr:</b> <?= h($offer['offer_number']); ?></div>
      <div class="kv"><b>Data:</b> <?= h($offerDate); ?></div>
      <div class="kv"><b>Monedă:</b> RON</div>
    </div>
    <div class="card">
      <h3>Beneficiar</h3>
      <div class="kv"><b>Companie:</b> <?= h($offer['client_name']); ?></div>
      <div class="kv"><b>CUI:</b> <?= h($offer['client_cui']); ?></div>
      <div class="kv"><b>Adresă:</b> <?= h($offer['client_address']); ?></div>
      <?php if ($clientContact): ?>
        <div class="kv"><b>Contact:</b> <?= h($clientContact['name']); ?></div>
        <div class="kv"><b>Telefon:</b> <?= h($clientContact['phone']); ?></div>
        <div class="kv"><b>Email:</b> <?= h($clientContact['email']); ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="items">
    <table>
      <thead>
        <tr>
          <th style="width:48px;">Nr.</th>
          <th>Descriere</th>
          <th class="right" style="width:100px;">Cant.</th>
          <th class="right" style="width:130px;">Preț unitar</th>
          <th class="right" style="width:140px;">Subtotal</th>
        </tr>
      </thead>
      <tbody>
      <?php
        $idx = 1;
        foreach ($items as $it):
            $qty = (int)$it['quantity'];
            $unit = (float)$it['unit_price'];
            $rowSubtotal = isset($it['subtotal']) ? (float)$it['subtotal'] : ($qty * $unit);
      ?>
        <tr>
          <td><?= $idx++; ?></td>
          <td><?= nl2br(h($it['description'])); ?></td>
          <td class="right"><?= h((string)$qty); ?></td>
          <td class="right"><?= money($unit); ?> RON</td>
          <td class="right"><?= money($rowSubtotal); ?> RON</td>
        </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" class="right">Subtotal</td>
          <td class="right"><?= money($subtotal); ?> RON</td>
        </tr>
        <?php if ($discountAmount > 0): ?>
        <tr style="color:#dc3545;">
          <td colspan="4" class="right">
            Discount 
            <?php if ($discountType === 'percent'): ?>
              (<?= number_format($discountAmount, 2); ?>%)
            <?php else: ?>
              (<?= money($discountAmount); ?> RON fix)
            <?php endif; ?>
          </td>
          <td class="right">-<?= money($discountValue); ?> RON</td>
        </tr>
        <?php endif; ?>
        <tr style="background:#f6f7f9;">
          <td colspan="4" class="right total">TOTAL</td>
          <td class="right total"><?= money($finalTotal); ?> RON</td>
        </tr>
      </tfoot>
    </table>
  </div>

  <?php if (!empty($offer['details'])): ?>
  <div class="sections">
    <h3 class="sec-title">Detalii</h3>
    <div class="sec-body"><?= nl2br(h($offer['details'])); ?></div>
  </div>
  <?php endif; ?>

  <div class="sections">
    <?= $sections ?>
  </div>

  <div class="legal">
    Această ofertă nu reprezintă factură și nu transferă proprietatea asupra bunurilor/serviciilor. Termenii comerciali și condițiile contractuale
    se aplică conform secțiunilor și legislației române în vigoare. Acceptarea ofertei se poate face prin răspuns scris (email) sau prin efectuarea plății
    conform instrucțiunilor comunicate.
  </div>

  <div class="footer">
    Document emis electronic de <?= h($offer['seller_company_name'] ?: ''); ?>.
  </div>

</div>

</body>
</html>