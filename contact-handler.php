<?php
// Configuration de session pour serveur de développement
session_save_path(sys_get_temp_dir());
session_start();

// Configuration sécurisée
define('MAX_ATTEMPTS', 5);
define('LOCKOUT_TIME', 300);
define('BREVO_API_KEY', 'xkeysib-[VOTRE_CLE_API_ICI]'); // Remplacez par votre vraie clé API
define('RECIPIENT_EMAIL', 'mail de reception'); // Remplacez par l'email de réception

// Fonction de nettoyage (garde votre sécurité actuelle)
function securize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Rate limiting (garde votre système actuel)
function checkRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $attempts_file = sys_get_temp_dir() . '/attempts_' . md5($ip) . '.txt';
    
    if (file_exists($attempts_file)) {
        $attempts = json_decode(file_get_contents($attempts_file), true);
        $current_time = time();
        
        $attempts = array_filter($attempts, function($timestamp) use ($current_time) {
            return ($current_time - $timestamp) < LOCKOUT_TIME;
        });
        
        if (count($attempts) >= MAX_ATTEMPTS) {
            return false;
        }
        
        $attempts[] = $current_time;
    } else {
        $attempts = [time()];
    }
    
    file_put_contents($attempts_file, json_encode($attempts));
    return true;
}

// Token CSRF (garde votre sécurité actuelle)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ENDPOINT SÉCURISÉ POUR RÉCUPÉRATION TOKEN CSRF (POST UNIQUEMENT)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'get_csrf_token') {
    header('Content-Type: application/json');
    
    // Vérifier que c'est bien une requête AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        echo json_encode([
            'csrf_token' => $_SESSION['csrf_token'],
            'status' => 'success'
        ]);
    } else {
        echo json_encode([
            'error' => 'Invalid request',
            'status' => 'error'
        ]);
    }
    exit;
}

// Fonction d'envoi via API Brevo (SÉCURISÉE)
function sendEmailViaBrevo($name, $email, $message) {
    $url = 'https://api.brevo.com/v3/smtp/email';
    
    $data = [
        'sender' => [
            'name' => 'HELIOSA Contact',
            'email' => 'mail de reception'
        ],
        'to' => [
            [
                'email' => RECIPIENT_EMAIL,
                'name' => 'HELIOSA'
            ]
        ],
        'subject' => '🌞 HELIOSA - Nouveau message depuis le site',
        'htmlContent' => "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #f0b952; border-bottom: 2px solid #f0b952; padding-bottom: 10px;'>
                    🌞 Nouveau message HELIOSA
                </h2>
                <div style='background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                    <p><strong>👤 Nom :</strong> " . htmlspecialchars($name) . "</p>
                    <p><strong>📧 Email :</strong> " . htmlspecialchars($email) . "</p>
                    <p><strong>📅 Date :</strong> " . date('d/m/Y à H:i:s') . "</p>
                    <p><strong>🌐 IP :</strong> " . $_SERVER['REMOTE_ADDR'] . "</p>
                </div>
                <div style='background: white; padding: 20px; border-left: 4px solid #f0b952;'>
                    <h3 style='margin-top: 0; color: #333;'>💬 Message :</h3>
                    <p style='line-height: 1.6; color: #555;'>" . nl2br(htmlspecialchars($message)) . "</p>
                </div>
                <div style='margin-top: 20px; padding: 10px; background: #e8f4f8; border-radius: 4px; font-size: 12px; color: #666;'>
                    Envoyé depuis le site HELIOSA - Système sécurisé
                </div>
            </div>
        ",
        'replyTo' => [
            'email' => $email,
            'name' => $name
        ]
    ];
    
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'api-key: ' . BREVO_API_KEY
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("Brevo API Response: " . $response);
    error_log("Brevo HTTP Code: " . $httpCode);
    
    return $httpCode >= 200 && $httpCode < 300;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Rate limiting (votre sécurité)
    if (!checkRateLimit()) {
        error_log("Rate limit dépassé - IP: " . $_SERVER['REMOTE_ADDR']);
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=rate_limit");
        exit;
    }
    
    // CSRF (votre sécurité)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("Tentative CSRF - IP: " . $_SERVER['REMOTE_ADDR']);
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=csrf");
        exit;
    }
    
    // Validation (votre sécurité)
    $name = securize($_POST["name"] ?? "");
    $email = filter_var(trim($_POST["email"] ?? ""), FILTER_VALIDATE_EMAIL);
    $message = securize($_POST["message"] ?? "");
    
    if (strlen($name) < 2 || strlen($name) > 100) {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=name_length");
        exit;
    }
    
    if (strlen($message) < 10 || strlen($message) > 1000) {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=message_length");
        exit;
    }
    
    if (!$email) {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=email_invalid");
        exit;
    }
    
    // Anti-spam (votre sécurité)
    $spam_words = ['viagra', 'casino', 'lottery', 'prize', 'winner'];
    $message_lower = strtolower($message);
    foreach ($spam_words as $spam) {
        if (strpos($message_lower, $spam) !== false) {
            error_log("Spam détecté - IP: " . $_SERVER['REMOTE_ADDR']);
            header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=spam");
            exit;
        }
    }
    
    // ENVOI SÉCURISÉ VIA BREVO API
    $success = sendEmailViaBrevo($name, $email, $message);
    
    if ($success) {
        error_log("✅ Email envoyé avec succès via Brevo API");
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?success=1");
    } else {
        error_log("❌ Échec envoi email via Brevo API");
        // Sauvegarde de fallback
        $log_entry = date('Y-m-d H:i:s') . " - ÉCHEC BREVO - " . $name . " (" . $email . "): " . $message . "\n";
        file_put_contents('/tmp/heliosa_emails_failed.log', $log_entry, FILE_APPEND | LOCK_EX);
        
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=send_failed");
    }
    exit;
}

header("Location: index.html");
exit;
?>