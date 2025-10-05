<?php
require_once 'config.php';
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$userId = (int)$_SESSION['user_id'];
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

$pageName = "Profil";
$MainPage = $AppName;
$MainPageh = "profile";
$pageId = 101;
$pageIds = 1;

include_once("WEB-INF/menu.php");
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    echo "Utilizatorul nu a fost găsit.";
    exit;
}
?>

<link rel="stylesheet" href="plugins/toastr/toastr.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --dark-bg: #1a1d2e;
    --card-bg: #ffffff;
    --border-radius: 16px;
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.12);
    --shadow-lg: 0 8px 32px rgba(0,0,0,0.16);
}

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
}

.profile-header {
    background: var(--primary-gradient);
    border-radius: var(--border-radius);
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-lg);
    position: relative;
    overflow: hidden;
}

.profile-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}

.profile-avatar-section {
    display: flex;
    align-items: center;
    gap: 2rem;
    position: relative;
    z-index: 1;
}

.avatar-upload-wrapper {
    position: relative;
}

.profile-avatar {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    border: 5px solid rgba(255,255,255,0.3);
    object-fit: cover;
    background: rgba(255,255,255,0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: white;
    font-weight: 600;
    box-shadow: var(--shadow-md);
    transition: all 0.3s ease;
}

.profile-avatar:hover {
    transform: scale(1.05);
    border-color: rgba(255,255,255,0.5);
}

.avatar-upload-btn {
    position: absolute;
    bottom: 5px;
    right: 5px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow-md);
    transition: all 0.3s ease;
}

.avatar-upload-btn:hover {
    transform: scale(1.1);
    background: #f8f9fa;
}

.avatar-upload-btn i {
    color: #667eea;
    font-size: 1.2rem;
}

#avatarInput {
    display: none;
}

.profile-info {
    color: white;
}

.profile-info h2 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 0.5rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.profile-info p {
    margin: 0.25rem 0;
    opacity: 0.9;
    font-size: 0.95rem;
}

.profile-info .badge {
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-weight: 500;
    margin-top: 0.5rem;
    display: inline-block;
}

.modern-card {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
    border: none;
    margin-bottom: 1.5rem;
    overflow: hidden;
    transition: all 0.3s ease;
}

.modern-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-2px);
}

.modern-card .card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: none;
    padding: 1.5rem;
    font-weight: 600;
    font-size: 1.1rem;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modern-card .card-header i {
    color: #667eea;
}

.modern-card .card-body {
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    font-weight: 500;
    color: #4a5568;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
    display: block;
}

.form-control {
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

.btn-save {
    background: var(--primary-gradient);
    border: none;
    padding: 1rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    color: white;
    font-size: 1rem;
    transition: all 0.3s ease;
    box-shadow: var(--shadow-md);
    width: 100%;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
    opacity: 0.95;
}

.input-icon {
    position: relative;
}

.input-icon i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #a0aec0;
}

.input-icon .form-control {
    padding-left: 2.75rem;
}

.save-indicator {
    display: none;
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: var(--success-gradient);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    box-shadow: var(--shadow-lg);
    z-index: 9999;
    animation: slideInUp 0.3s ease;
}

@keyframes slideInUp {
    from {
        transform: translateY(100px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.section-divider {
    height: 2px;
    background: linear-gradient(90deg, transparent 0%, #e2e8f0 50%, transparent 100%);
    margin: 2rem 0;
}

@media (max-width: 768px) {
    .profile-avatar-section {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-info h2 {
        font-size: 1.5rem;
    }
}
</style>

<div class="container-fluid px-4 py-4">
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar-section">
            <div class="avatar-upload-wrapper">
                <div class="profile-avatar" id="profileAvatar">
                    <?php if (!empty($user['logo'])): ?>
                        <img src="<?= htmlspecialchars($user['logo']) ?>" alt="Logo" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                    <?php else: ?>
                        <?= strtoupper(substr($user['name'], 0, 2)) ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="avatar-upload-btn" onclick="document.getElementById('avatarInput').click()">
                    <i class="fas fa-camera"></i>
                </button>
                <input type="file" id="avatarInput" accept="image/*">
            </div>
            <div class="profile-info">
                <h2><?= htmlspecialchars($user['name']) ?></h2>
                <p><i class="fas fa-envelope mr-2"></i><?= htmlspecialchars($user['email'] ?? 'Email nesetat') ?></p>
                <p><i class="fas fa-building mr-2"></i><?= htmlspecialchars($user['company_name'] ?? 'Companie nesetată') ?></p>
                <span class="badge"><i class="fas fa-check-circle mr-1"></i>Cont Activ</span>
            </div>
        </div>
    </div>

    <form id="configForm" method="POST">
        <div class="row">
            <!-- Contact Information -->
            <div class="col-lg-6">
                <div class="modern-card">
                    <div class="card-header">
                        <i class="fas fa-user"></i>
                        Informații Personale
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Nume pe platformă</label>
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <div class="input-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($user['contact_email'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Telefon</label>
                            <div class="input-icon">
                                <i class="fas fa-phone"></i>
                                <input type="text" name="telefon" class="form-control" value="<?= htmlspecialchars($user['telefon']) ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Company Information -->
            <div class="col-lg-6">
                <div class="modern-card">
                    <div class="card-header">
                        <i class="fas fa-building"></i>
                        Informații Companie
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Nume firmă</label>
                            <div class="input-icon">
                                <i class="fas fa-building"></i>
                                <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($user['company_name'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">CUI</label>
                            <div class="input-icon">
                                <i class="fas fa-id-card"></i>
                                <input type="text" name="cui" class="form-control" value="<?= htmlspecialchars($user['cui'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Reg. Com.</label>
                            <div class="input-icon">
                                <i class="fas fa-file-alt"></i>
                                <input type="text" name="company_cif" class="form-control" value="<?= htmlspecialchars($user['company_cif'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Banking Information -->
            <div class="col-lg-6">
                <div class="modern-card">
                    <div class="card-header">
                        <i class="fas fa-university"></i>
                        Informații Bancare
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">IBAN</label>
                            <div class="input-icon">
                                <i class="fas fa-credit-card"></i>
                                <input type="text" name="iban" class="form-control" value="<?= htmlspecialchars($user['iban'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Nume Bancă</label>
                            <div class="input-icon">
                                <i class="fas fa-university"></i>
                                <input type="text" name="banc_name" class="form-control" value="<?= htmlspecialchars($user['banc_name'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="col-lg-6">
                <div class="modern-card">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i>
                        Informații Adiționale
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="form-label">Website</label>
                            <div class="input-icon">
                                <i class="fas fa-globe"></i>
                                <input type="text" name="company_site" class="form-control" value="<?= htmlspecialchars($user['company_site'] ?? '') ?>" placeholder="https://">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Adresă de facturare</label>
                            <div class="input-icon">
                                <i class="fas fa-map-marker-alt"></i>
                                <input type="text" name="billing_address" class="form-control" value="<?= htmlspecialchars($user['billing_address'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save Button -->
            <div class="col-12">
                <div class="modern-card">
                    <div class="card-body text-center">
                        <button class="btn-save" type="submit">
                            <i class="fas fa-save mr-2"></i>Salvează Modificările
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="save-indicator" id="saveIndicator">
    <i class="fas fa-check-circle mr-2"></i>Salvat cu succes!
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="plugins/toastr/toastr.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

<script>
$(function(){
    // Avatar upload
    $('#avatarInput').on('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            toastr.error('Te rog selectează o imagine validă');
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            toastr.error('Imaginea este prea mare. Maxim 5MB');
            return;
        }

        const formData = new FormData();
        formData.append('avatar', file);
        formData.append('csrf_token', '<?=$csrf_token?>');

        $.ajax({
            url: 'ajax_upload_avatar.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        $('#profileAvatar').html(`<img src="${e.target.result}" alt="Logo" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`);
                    };
                    reader.readAsDataURL(file);
                    
                    toastr.success('Logo actualizat cu succes!');
                    $('#saveIndicator').fadeIn().delay(2000).fadeOut();
                } else {
                    toastr.error(response.error || 'Eroare la încărcarea logo-ului');
                }
            },
            error: function() {
                toastr.error('Eroare la încărcarea logo-ului. Încearcă din nou.');
            }
        });
    });

    // Form submit
    $('#configForm').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: 'ajax_update_profile.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message || 'Setările au fost salvate.');
                    $('#saveIndicator').fadeIn().delay(2000).fadeOut();
                    
                    // Update header info
                    if ($('input[name="name"]').val()) {
                        $('.profile-info h2').text($('input[name="name"]').val());
                    }
                } else {
                    toastr.error(response.error || 'Eroare la salvare');
                }
            },
            error: function() {
                toastr.error('A apărut o eroare la salvare. Încearcă din nou.');
            }
        });
    });

    // Toastr configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        timeOut: 3000
    };
});
</script>

<?php include_once("WEB-INF/footer.php"); ?>