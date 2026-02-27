<?php
/**
 * ============================================
 * MESA HERMÉTICA — Contact Form Handler
 * ============================================
 * Recebe dados do formulário, valida, e envia
 * e-mail via SMTP (ou mail() como fallback).
 * ============================================
 */

// ─── Carregar .env ───
require_once __DIR__ . '/env.php';
loadEnv(__DIR__ . '/../.env');

// ─── Headers ───
header('Content-Type: application/json; charset=utf-8');

// CORS
$allowedOrigins = array_map('trim', explode(',', env('ALLOWED_ORIGINS', 'http://localhost')));
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// ─── Rate Limiting (simple file-based) ───
$rateLimit = (int) env('RATE_LIMIT', 5);
$rateLimitFile = sys_get_temp_dir() . '/mesahermetica_rate_' . md5($_SERVER['REMOTE_ADDR'] ?? 'unknown');

if (file_exists($rateLimitFile)) {
    $rateData = json_decode(file_get_contents($rateLimitFile), true);
    $rateData['attempts'] = array_filter($rateData['attempts'], function ($ts) {
        return $ts > time() - 3600; // keep last hour only
    });

    if (count($rateData['attempts']) >= $rateLimit) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Muitas tentativas. Tente novamente em uma hora.'
        ]);
        exit;
    }
} else {
    $rateData = ['attempts' => []];
}

// ─── Honeypot check ───
if (!empty($_POST['website'])) {
    // Bot detected — fake success
    echo json_encode(['success' => true]);
    exit;
}

// ─── Sanitize & Validate ───
function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

$name    = sanitize($_POST['name'] ?? '');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone   = sanitize($_POST['phone'] ?? '');
$subject = sanitize($_POST['subject'] ?? '');
$message = sanitize($_POST['message'] ?? '');

$errors = [];

// Name
if (empty($name)) {
    $errors['name'] = 'Por favor, informe seu nome.';
} elseif (mb_strlen($name) < 3) {
    $errors['name'] = 'O nome deve ter pelo menos 3 caracteres.';
} elseif (mb_strlen($name) > 100) {
    $errors['name'] = 'O nome deve ter no máximo 100 caracteres.';
}

// Email
if (empty($email)) {
    $errors['email'] = 'Por favor, informe seu e-mail.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Informe um e-mail válido.';
}

// Subject
$validSubjects = ['duvida', 'agendamento', 'feedback', 'outro'];
if (empty($subject)) {
    $errors['subject'] = 'Selecione um assunto.';
} elseif (!in_array($subject, $validSubjects, true)) {
    $errors['subject'] = 'Assunto inválido.';
}

// Message
if (empty($message)) {
    $errors['message'] = 'Escreva sua mensagem.';
} elseif (mb_strlen($message) < 10) {
    $errors['message'] = 'A mensagem deve ter pelo menos 10 caracteres.';
} elseif (mb_strlen($message) > 2000) {
    $errors['message'] = 'A mensagem deve ter no máximo 2000 caracteres.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// ─── Subject labels ───
$subjectLabels = [
    'duvida'      => 'Dúvida sobre a Mesa Radiônica',
    'agendamento' => 'Agendamento de sessão',
    'feedback'    => 'Feedback / Depoimento',
    'outro'       => 'Outro assunto',
];
$subjectLabel = $subjectLabels[$subject] ?? $subject;

// ─── Build email ───
$mailTo       = env('MAIL_TO', 'contato@mesahermetica.com.br');
$mailFromName = env('MAIL_FROM_NAME', 'Mesa Hermética');
$mailFromEmail = env('MAIL_FROM_EMAIL', 'noreply@mesahermetica.com.br');
$siteName     = env('SITE_NAME', 'Mesa Hermética');

$emailSubject = "[$siteName] $subjectLabel — $name";

$emailBody = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"></head>
<body style="font-family: 'Montserrat', Arial, sans-serif; color: #1A1A2E; max-width: 600px; margin: 0 auto; padding: 20px;">
  <div style="background: linear-gradient(135deg, #5D328E 0%, #3E1F6B 100%); padding: 28px 32px; border-radius: 16px 16px 0 0;">
    <h1 style="margin: 0; color: #E2C275; font-size: 1.4rem;">$siteName</h1>
    <p style="margin: 6px 0 0; color: rgba(255,255,255,0.7); font-size: 0.9rem;">Nova mensagem do formulário de contato</p>
  </div>
  
  <div style="background: #F8F6F1; padding: 32px; border: 1px solid #E0DED8; border-top: none;">
    <table style="width: 100%; border-collapse: collapse;">
      <tr>
        <td style="padding: 12px 0; border-bottom: 1px solid #E0DED8; font-weight: 600; width: 120px; vertical-align: top;">Nome</td>
        <td style="padding: 12px 0; border-bottom: 1px solid #E0DED8;">$name</td>
      </tr>
      <tr>
        <td style="padding: 12px 0; border-bottom: 1px solid #E0DED8; font-weight: 600; vertical-align: top;">E-mail</td>
        <td style="padding: 12px 0; border-bottom: 1px solid #E0DED8;"><a href="mailto:$email" style="color: #5D328E;">$email</a></td>
      </tr>
      <tr>
        <td style="padding: 12px 0; border-bottom: 1px solid #E0DED8; font-weight: 600; vertical-align: top;">Telefone</td>
        <td style="padding: 12px 0; border-bottom: 1px solid #E0DED8;">$phone</td>
      </tr>
      <tr>
        <td style="padding: 12px 0; border-bottom: 1px solid #E0DED8; font-weight: 600; vertical-align: top;">Assunto</td>
        <td style="padding: 12px 0; border-bottom: 1px solid #E0DED8;">$subjectLabel</td>
      </tr>
      <tr>
        <td style="padding: 12px 0; font-weight: 600; vertical-align: top;">Mensagem</td>
        <td style="padding: 12px 0; line-height: 1.65;">$message</td>
      </tr>
    </table>
  </div>
  
  <div style="background: #1A1A2E; padding: 18px 32px; border-radius: 0 0 16px 16px; text-align: center;">
    <p style="margin: 0; color: rgba(255,255,255,0.4); font-size: 0.78rem;">
      Enviado pelo formulário de contato — $siteName
    </p>
  </div>
</body>
</html>
HTML;

// ─── Send email ───
$smtpHost = env('SMTP_HOST', 'localhost');
$smtpPort = (int) env('SMTP_PORT', 1025);
$smtpUser = env('SMTP_USER', '');
$smtpPass = env('SMTP_PASS', '');

$sent = false;

// Try SMTP via fsockopen (no external dependencies)
if (!empty($smtpUser) && !empty($smtpPass) && $smtpHost !== 'localhost') {
    $sent = sendSmtp($smtpHost, $smtpPort, $smtpUser, $smtpPass, $mailFromEmail, $mailFromName, $mailTo, $emailSubject, $emailBody);
} else {
    // Fallback: PHP mail() for local development
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $mailFromName <$mailFromEmail>\r\n";
    $headers .= "Reply-To: $name <$email>\r\n";
    $headers .= "X-Mailer: MesaHermetica/1.0\r\n";

    $sent = @mail($mailTo, $emailSubject, $emailBody, $headers);

    // If mail() fails in local dev, save to file as fallback
    if (!$sent) {
        $logDir = __DIR__ . '/../storage/emails';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/' . date('Y-m-d_H-i-s') . '_' . uniqid() . '.html';
        $logContent  = "TO: $mailTo\n";
        $logContent .= "FROM: $mailFromName <$mailFromEmail>\n";
        $logContent .= "REPLY-TO: $name <$email>\n";
        $logContent .= "SUBJECT: $emailSubject\n";
        $logContent .= "DATE: " . date('Y-m-d H:i:s') . "\n";
        $logContent .= "---\n\n";
        $logContent .= $emailBody;

        file_put_contents($logFile, $logContent);
        $sent = true; // Consider saved = sent in dev mode
    }
}

// ─── Record rate limit ───
$rateData['attempts'][] = time();
file_put_contents($rateLimitFile, json_encode($rateData));

// ─── Response ───
if ($sent) {
    echo json_encode([
        'success' => true,
        'message' => 'Mensagem enviada com sucesso!'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao enviar a mensagem. Tente novamente.'
    ]);
}

// ─────────────────────────────────────────────
// SMTP Function (basic, no dependencies)
// ─────────────────────────────────────────────
function sendSmtp(
    string $host,
    int $port,
    string $user,
    string $pass,
    string $fromEmail,
    string $fromName,
    string $to,
    string $subject,
    string $body
): bool {
    $secure = env('SMTP_SECURE', 'tls');
    $prefix = ($secure === 'ssl') ? 'ssl://' : '';

    $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 10);
    if (!$socket) {
        error_log("SMTP Error: Could not connect to $host:$port — $errstr ($errno)");
        return false;
    }

    stream_set_timeout($socket, 10);

    // Helper to send command and read response
    $send = function (string $cmd) use ($socket): string {
        fwrite($socket, $cmd . "\r\n");
        return fgets($socket, 512) ?: '';
    };

    $read = function () use ($socket): string {
        return fgets($socket, 512) ?: '';
    };

    try {
        $read(); // greeting

        $send("EHLO " . gethostname());
        // Read all EHLO responses
        do {
            $line = $read();
        } while (isset($line[3]) && $line[3] === '-');

        // STARTTLS
        if ($secure === 'tls') {
            $send("STARTTLS");
            $read();
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);

            $send("EHLO " . gethostname());
            do {
                $line = $read();
            } while (isset($line[3]) && $line[3] === '-');
        }

        // AUTH LOGIN
        $send("AUTH LOGIN");
        $read();
        $send(base64_encode($user));
        $read();
        $send(base64_encode($pass));
        $resp = $read();

        if (substr($resp, 0, 3) !== '235') {
            error_log("SMTP Auth failed: $resp");
            fclose($socket);
            return false;
        }

        $send("MAIL FROM:<$fromEmail>");
        $read();
        $send("RCPT TO:<$to>");
        $read();
        $send("DATA");
        $read();

        // Build message
        $boundary = md5(uniqid(time()));
        $msg  = "From: $fromName <$fromEmail>\r\n";
        $msg .= "To: $to\r\n";
        $msg .= "Reply-To: $fromName <$fromEmail>\r\n";
        $msg .= "Subject: $subject\r\n";
        $msg .= "MIME-Version: 1.0\r\n";
        $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
        $msg .= "X-Mailer: MesaHermetica/1.0\r\n";
        $msg .= "\r\n";
        $msg .= $body;
        $msg .= "\r\n.\r\n";

        fwrite($socket, $msg);
        $resp = $read();

        $send("QUIT");
        fclose($socket);

        return substr($resp, 0, 3) === '250';
    } catch (\Throwable $e) {
        error_log("SMTP Error: " . $e->getMessage());
        if (is_resource($socket)) {
            fclose($socket);
        }
        return false;
    }
}
