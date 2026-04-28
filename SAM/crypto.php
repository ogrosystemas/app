<?php
// crypto.php — AES-256-GCM compatível com keygen-lupa.html

function crypto_derive_key(string $secret): string {
    return hash_pbkdf2('sha256', $secret, 'erp-ml-license-salt-v1', 100000, 32, true);
}

function crypto_decrypt_license(string $licenseKey, string $masterSecret): array {
    $combined = base64_decode(strtr($licenseKey, '-_', '+/') . str_repeat('=', (4 - strlen($licenseKey) % 4) % 4));
    if (!$combined || strlen($combined) < 29) throw new Exception('LICENSE_INVALID_FORMAT');

    $iv         = substr($combined, 0, 12);
    $tag        = substr($combined, 12, 16);
    $ciphertext = substr($combined, 28);
    $key        = crypto_derive_key($masterSecret);

    $plain = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($plain === false) throw new Exception('LICENSE_TAMPERED');

    $payload = json_decode($plain, true);
    if (!$payload) throw new Exception('LICENSE_INVALID_JSON');

    if (strtotime($payload['expiryDate'] ?? '2000-01-01') < time()) throw new Exception('LICENSE_EXPIRED');
    return $payload;
}

function crypto_encrypt_token(string $plain): string {
    $key = hex2bin(TOKEN_KEY ?: str_repeat('0', 64));
    $iv  = random_bytes(12);
    $tag = '';
    $enc = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    return base64_encode($iv . $tag . $enc);
}

function crypto_decrypt_token(string $enc): string {
    $key      = hex2bin(TOKEN_KEY ?: str_repeat('0', 64));
    $combined = base64_decode($enc);
    $iv         = substr($combined, 0, 12);
    $tag        = substr($combined, 12, 16);
    $ciphertext = substr($combined, 28);
    $plain = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($plain === false) throw new Exception('TOKEN_DECRYPT_FAILED');
    return $plain;
}
