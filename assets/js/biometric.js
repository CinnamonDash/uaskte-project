// biometric.js – WebAuthn biometric gate for admin pages

let biometricVerified = false;
const BIOMETRIC_SESSION_KEY = 'biometric_verified_session';

async function initBiometricGate() {
    const gate = document.getElementById('biometric-gate');
    const content = document.getElementById('user-management-content');

    if (!gate || !content) return;

    // Cek session storage (berlaku selama tab terbuka)
    if (sessionStorage.getItem(BIOMETRIC_SESSION_KEY) === 'true') {
        gate.style.display = 'none';
        content.classList.remove('hidden');
        return;
    }

    gate.style.display = 'flex';
    content.classList.add('hidden');

    // Cek apakah WebAuthn tersedia
    if (!window.PublicKeyCredential) {
        document.getElementById('biometric-status').innerHTML =
            '<span class="text-warning">⚠️ Biometrik tidak tersedia di perangkat ini. Memuat dalam 3 detik...</span>';
        setTimeout(() => {
            gate.style.display = 'none';
            content.classList.remove('hidden');
            sessionStorage.setItem(BIOMETRIC_SESSION_KEY, 'true');
        }, 3000);
        return;
    }

    // Cek apakah platform authenticator tersedia
    try {
        const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
        if (!available) {
            document.getElementById('biometric-status').innerHTML =
                '<span class="text-warning">⚠️ Platform authenticator tidak tersedia. Silakan lanjutkan.</span>';
            document.getElementById('btn-biometric-verify').textContent = 'Lanjutkan';
            document.getElementById('btn-biometric-verify').onclick = skipBiometric;
        }
    } catch (e) {
        console.warn('WebAuthn check failed:', e);
    }
}

async function verifyBiometric() {
    const statusEl = document.getElementById('biometric-status');
    const btn = document.getElementById('btn-biometric-verify');

    btn.disabled = true;
    statusEl.innerHTML = '<span class="text-info">🔍 Memverifikasi biometrik...</span>';

    try {
        // Buat challenge random
        const challenge = new Uint8Array(32);
        crypto.getRandomValues(challenge);

        const credential = await navigator.credentials.get({
            publicKey: {
                challenge: challenge,
                timeout: 60000,
                userVerification: 'required',
                rpId: window.location.hostname,
            }
        });

        if (credential) {
            statusEl.innerHTML = '<span class="text-success">✅ Verifikasi berhasil!</span>';
            biometricVerified = true;
            sessionStorage.setItem(BIOMETRIC_SESSION_KEY, 'true');

            setTimeout(() => {
                document.getElementById('biometric-gate').style.display = 'none';
                document.getElementById('user-management-content').classList.remove('hidden');
                // Animasi slide in
                document.getElementById('user-management-content').style.animation = 'slideInUp 0.4s ease';
            }, 800);
        }
    } catch (err) {
        btn.disabled = false;

        if (err.name === 'NotAllowedError') {
            statusEl.innerHTML = '<span class="text-error">❌ Verifikasi dibatalkan atau ditolak.</span>';
        } else if (err.name === 'NotSupportedError' || err.name === 'SecurityError') {
            // Fallback jika tidak ada credential terdaftar
            statusEl.innerHTML = '<span class="text-warning">⚠️ Belum ada biometrik terdaftar. Akses diberikan untuk demo.</span>';
            setTimeout(skipBiometric, 2000);
        } else {
            statusEl.innerHTML = `<span class="text-error">❌ Error: ${err.message}</span>`;
        }
    }
}

function skipBiometric() {
    sessionStorage.setItem(BIOMETRIC_SESSION_KEY, 'true');
    document.getElementById('biometric-gate').style.display = 'none';
    const content = document.getElementById('user-management-content');
    content.classList.remove('hidden');
    content.style.animation = 'slideInUp 0.4s ease';
}

// Inisialisasi saat halaman dimuat
document.addEventListener('DOMContentLoaded', initBiometricGate);
