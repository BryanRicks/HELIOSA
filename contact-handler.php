<?php
session_start();

// Configuration
define('MAX_ATTEMPTS', 5); // 5 tentatives max par IP
define('LOCKOUT_TIME', 300); // 5 minutes de blocage
define('RECIPIENT_EMAIL', 'contact@heliosa-pv.fr');

// Fonction de nettoyage avancé
function securize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Protection contre le spam par IP
function checkRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $attempts_file = 'attempts_' . md5($ip) . '.txt';
    
    if (file_exists($attempts_file)) {
        $attempts = json_decode(file_get_contents($attempts_file), true);
        $current_time = time();
        
        // Nettoyer les anciennes tentatives
        $attempts = array_filter($attempts, function($timestamp) use ($current_time) {
            return ($current_time - $timestamp) < LOCKOUT_TIME;
        });
        
        if (count($attempts) >= MAX_ATTEMPTS) {
            return false; // Trop de tentatives
        }
        
        $attempts[] = $current_time;
    } else {
        $attempts = [time()];
    }
    
    file_put_contents($attempts_file, json_encode($attempts));
    return true;
}

// Génération token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Headers de sécurité
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

echo json_encode(['token' => $_SESSION['csrf_token']]);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Vérification rate limiting
    if (!checkRateLimit()) {
        http_response_code(429);
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=rate_limit");
        exit;
    }
    
    // 2. Vérification token CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        error_log("Tentative CSRF détectée depuis IP: " . $_SERVER['REMOTE_ADDR']);
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=csrf");
        exit;
    }
    
    // 3. Validation et nettoyage des données
    $name = securize($_POST["name"] ?? "");
    $email = filter_var(trim($_POST["email"] ?? ""), FILTER_VALIDATE_EMAIL);
    $message = securize($_POST["message"] ?? "");
    
    // 4. Validation des longueurs
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
    
    // 5. Protection anti-spam basique
    $spam_words = ['viagra', 'casino', 'lottery', 'prize', 'winner'];
    $message_lower = strtolower($message);
    foreach ($spam_words as $spam) {
        if (strpos($message_lower, $spam) !== false) {
            error_log("Message spam détecté depuis " . $_SERVER['REMOTE_ADDR']);
            header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=spam");
            exit;
        }
    }
    
    // 6. Préparation email sécurisé
    $subject = "Nouveau message depuis le site HELIOSA";
    $safe_name = preg_replace('/[^a-zA-Z0-9\s\-_àáâãäåçèéêëìíîïñòóôõöùúûüýÿ]/u', '', $name);
    
    $body = "=== MESSAGE DEPUIS LE SITE HELIOSA ===\n\n";
    $body .= "Nom: " . $safe_name . "\n";
    $body .= "Email: " . $email . "\n";
    $body .= "Date: " . date('d/m/Y à H:i:s') . "\n";
    $body .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n\n";
    $body .= "Message:\n" . $message . "\n\n";
    $body .= "---\nEnvoyé depuis: " . $_SERVER['HTTP_HOST'];
    
    // 7. En-têtes sécurisés
    $headers = array();
    $headers[] = "From: noreply@heliosa-pv.fr";
    $headers[] = "Reply-To: " . $email;
    $headers[] = "Content-Type: text/plain; charset=UTF-8";
    $headers[] = "X-Mailer: PHP/" . phpversion();
    $headers[] = "X-Priority: 3";
    
    // 8. Envoi sécurisé
    if (mail(RECIPIENT_EMAIL, $subject, $body, implode("\r\n", $headers))) {
        // Log succès
        error_log("Email envoyé avec succès depuis " . $_SERVER['REMOTE_ADDR'] . " - " . $email);
        
        // Régénérer token CSRF
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?success=1");
    } else {
        error_log("Erreur envoi email depuis " . $_SERVER['REMOTE_ADDR']);
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=send");
    }
    exit;
}

// Accès direct interdit
header("Location: index.html");
exit;
?>