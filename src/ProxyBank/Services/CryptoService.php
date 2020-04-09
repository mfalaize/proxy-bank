<?php


namespace ProxyBank\Services;


class CryptoService
{
    const ENCRYPT_METHOD = "AES-256-CBC";
    const HASH_ALGO = "sha256";
    const IV_LENGTH = 16;
    const SECRET_FILE = "secret.txt";
    const SECRET_KEY_LENGTH = 128;

    private $rootDir;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
    }

    public function encrypt(string $plaintext): string {
        $password = $this->getSecretPassword();
        $key = hash(self::HASH_ALGO, $password, true);
        $iv = openssl_random_pseudo_bytes(self::IV_LENGTH);

        $ciphertext = openssl_encrypt($plaintext, self::ENCRYPT_METHOD, $key, OPENSSL_RAW_DATA, $iv);
        $hash = hash_hmac(self::HASH_ALGO, $ciphertext . $iv, $key, true);

        return $iv . $hash . $ciphertext;
    }

    public function decrypt(string $ivHashCiphertext): string {
        $password = $this->getSecretPassword();
        $iv = substr($ivHashCiphertext, 0, self::IV_LENGTH);
        $hash = substr($ivHashCiphertext, self::IV_LENGTH, self::IV_LENGTH * 2);
        $ciphertext = substr($ivHashCiphertext, self::IV_LENGTH * 3);
        $key = hash(self::HASH_ALGO, $password, true);

        if (!hash_equals(hash_hmac(self::HASH_ALGO, $ciphertext . $iv, $key, true), $hash)) {
            return false;
        }

        return openssl_decrypt($ciphertext, self::ENCRYPT_METHOD, $key, OPENSSL_RAW_DATA, $iv);
    }

    public function getSecretFilePath(): string {
        return $this->rootDir . DIRECTORY_SEPARATOR . self::SECRET_FILE;
    }

    public function getSecretPassword(): string {
        if (!file_exists($this->getSecretFilePath())) {
            $this->generateSecretPasswordFile();
        }
        $file = fopen($this->getSecretFilePath(), "r");
        $password = fread($file, filesize($this->getSecretFilePath()));
        fclose($file);
        return $password;
    }

    public function generateRandomSecret(): string {
        $possibles_chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789,;:!?./%*#{}[]()+=-_\\&@|$";
        $secret = "";
        for ($i = 0; $i < self::SECRET_KEY_LENGTH; $i++) {
            $secret .= $possibles_chars[random_int(0, strlen($possibles_chars) - 1)];
        }
        return $secret;
    }

    public function generateSecretPasswordFile(): void {
        $file = fopen($this->getSecretFilePath(), "w");
        fwrite($file, $this->generateRandomSecret());
        fclose($file);
    }
}
