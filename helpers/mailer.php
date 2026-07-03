<?php
/**
 * Mailer Helper – kirim OTP via Email
 * Mendukung PHP mail() bawaan Laragon dan opsional SMTP via PHPMailer
 */

// ── Config Email ──────────────────────────────────────────────
define('MAIL_FROM',    'noreply@uaskte.local');
define('MAIL_FROM_NAME', 'UAS KTE System');
define('SMTP_HOST',    'smtp.gmail.com');
define('SMTP_PORT',    587);
define('SMTP_USER',    '');   // isi dengan email SMTP Anda
define('SMTP_PASS',    '');   // isi dengan app password
define('SMTP_ENABLED', false); // ganti ke true jika pakai SMTP

/**
 * Kirim OTP ke email via PHP mail() bawaan atau SMTP
 *
 * @param string $toEmail  Alamat email tujuan
 * @param string $toName   Nama penerima
 * @param string $otp      Kode OTP 6 digit
 * @return bool            true jika berhasil terkirim
 */
function sendOtpEmail(string $toEmail, string $toName, string $otp): bool
{
    $appName = defined('APP_NAME') ? APP_NAME : 'UAS KTE';

    $subject = "Kode OTP Registrasi – {$appName}";

    $body = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kode OTP Registrasi</title></head>
<body style="margin:0;padding:0;background:#0F0E17;font-family:'Inter',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#0F0E17;padding:40px 16px;">
    <tr><td align="center">
      <table width="100%" style="max-width:480px;background:rgba(22,21,31,0.95);border:1px solid rgba(255,255,255,0.08);border-radius:16px;overflow:hidden;">
        <!-- Header -->
        <tr><td style="background:linear-gradient(135deg,#6C63FF,#A855F7);padding:28px 32px;text-align:center;">
          <h1 style="margin:0;color:#fff;font-size:22px;font-weight:800;letter-spacing:-0.5px;">{$appName}</h1>
          <p style="margin:6px 0 0;color:rgba(255,255,255,0.8);font-size:13px;">Verifikasi Akun Baru</p>
        </td></tr>
        <!-- Body -->
        <tr><td style="padding:32px;">
          <p style="color:#E8E6F0;font-size:15px;margin:0 0 8px;">Halo, <strong>{$toName}</strong> 👋</p>
          <p style="color:#8B89A0;font-size:14px;line-height:1.6;margin:0 0 28px;">
            Gunakan kode OTP berikut untuk menyelesaikan registrasi akun Anda di <strong style="color:#A855F7;">{$appName}</strong>. Kode berlaku selama <strong>5 menit</strong>.
          </p>

          <!-- OTP Box -->
          <div style="text-align:center;margin:0 0 28px;">
            <div style="display:inline-block;background:#0F0E17;border:2px solid rgba(108,99,255,0.5);border-radius:12px;padding:20px 40px;">
              <span style="font-size:38px;font-weight:800;letter-spacing:12px;color:#fff;font-family:'Courier New',monospace;">{$otp}</span>
            </div>
          </div>

          <div style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.25);border-radius:10px;padding:14px 18px;margin-bottom:24px;">
            <p style="margin:0;color:#FCD34D;font-size:13px;">
              ⚠️ <strong>Jangan bagikan kode ini kepada siapapun</strong>, termasuk tim {$appName}.
            </p>
          </div>

          <p style="color:#8B89A0;font-size:13px;margin:0;">
            Jika Anda tidak melakukan registrasi ini, abaikan email ini.
          </p>
        </td></tr>
        <!-- Footer -->
        <tr><td style="padding:18px 32px;border-top:1px solid rgba(255,255,255,0.06);text-align:center;">
          <p style="margin:0;color:#8B89A0;font-size:12px;">&copy; 2026 {$appName}. Semua hak dilindungi.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;

    // ── Kirim via SMTP (PHPMailer) jika dikonfigurasi ─────────
    if (SMTP_ENABLED && SMTP_USER && class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom(SMTP_USER, MAIL_FROM_NAME);
            $mail->addAddress($toEmail, $toName);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log('Mailer SMTP Error: ' . $e->getMessage());
            return false;
        }
    }

    // ── Fallback: PHP mail() bawaan ───────────────────────────
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    return @mail($toEmail, $subject, $body, $headers);
}
