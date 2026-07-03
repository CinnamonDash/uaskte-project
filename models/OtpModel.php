<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../models/UserModel.php';

class OtpModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Generate & simpan OTP
    public function generate(int $userId): string {
        // Hapus OTP lama
        $stmt = $this->pdo->prepare("DELETE FROM otp_log WHERE user_id = ?");
        $stmt->execute([$userId]);

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Gunakan NOW() MySQL agar timezone konsisten dengan saat verify
        $stmt = $this->pdo->prepare("INSERT INTO otp_log (user_id, otp_code, expired_at) VALUES (?, ?, NOW() + INTERVAL " . OTP_LIFETIME . " SECOND)");
        $stmt->execute([$userId, $otp]);

        return $otp;
    }

    // Verifikasi OTP
    public function verify(int $userId, string $otp): bool {
        $stmt = $this->pdo->prepare("
            SELECT * FROM otp_log
            WHERE user_id = ? AND otp_code = ? AND is_used = 0 AND expired_at > NOW()
            ORDER BY id DESC LIMIT 1
        ");
        $stmt->execute([$userId, $otp]);
        $row = $stmt->fetch();

        if ($row) {
            // Tandai sebagai sudah digunakan
            $stmt2 = $this->pdo->prepare("UPDATE otp_log SET is_used = 1 WHERE id = ?");
            $stmt2->execute([$row['id']]);
            return true;
        }
        return false;
    }

    // Kirim OTP via WhatsApp Fonnte
    public function sendWhatsApp(string $noWa, string $otp, string $nama): bool {
        $message = "Halo *{$nama}*,\n\nKode OTP Anda untuk login ke *" . APP_NAME . "* adalah:\n\n*{$otp}*\n\nKode berlaku 5 menit. Jangan berikan kepada siapapun.";

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL            => FONNTE_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'target'  => $noWa,
                'message' => $message,
            ]),
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . FONNTE_TOKEN,
            ],
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $result = json_decode($response, true);
        return ($httpCode === 200 && isset($result['status']) && $result['status'] === true);
    }
}
