// biometric.js – WebAuthn biometric for admin CRUD confirmation

async function confirmActionWithBiometric(actionName, callback) {
    // Cek apakah WebAuthn tersedia
    if (!window.PublicKeyCredential) {
        alert('Biometrik tidak tersedia di perangkat ini. Melanjutkan aksi: ' + actionName);
        callback();
        return;
    }

    try {
        const available = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
        if (!available) {
            alert('Platform authenticator tidak tersedia. Melanjutkan aksi: ' + actionName);
            callback();
            return;
        }
    } catch (e) {
        console.warn('WebAuthn check failed:', e);
    }

    try {
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
            // Verifikasi berhasil
            callback();
        }
    } catch (err) {
        if (err.name === 'NotAllowedError') {
            showToast('Verifikasi biometrik dibatalkan atau ditolak.', 'error');
        } else if (err.name === 'NotSupportedError' || err.name === 'SecurityError') {
            // Fallback jika tidak ada credential terdaftar
            alert('Belum ada biometrik terdaftar. Mengizinkan aksi (demo).');
            callback();
        } else {
            showToast(`Error biometrik: ${err.message}`, 'error');
        }
    }
}

