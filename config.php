<?php
declare(strict_types=1);
const DB_HOST='127.0.0.1'; const DB_NAME='brangkas_asril'; const DB_USER='root'; const DB_PASS='';
function db(): PDO { static $pdo; if (!$pdo) $pdo=new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]); return $pdo; }
function ensure_database(): void {
  $server=new PDO('mysql:host='.DB_HOST.';charset=utf8mb4',DB_USER,DB_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  $server->exec('CREATE DATABASE IF NOT EXISTS `'.DB_NAME.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
  $server->exec('USE `'.DB_NAME.'`');
  $server->exec('CREATE TABLE IF NOT EXISTS app_settings (setting_name VARCHAR(100) NOT NULL PRIMARY KEY, setting_value TEXT NOT NULL) ENGINE=InnoDB');
  $server->exec('CREATE TABLE IF NOT EXISTS vault_contents (id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, title VARCHAR(150) NOT NULL, ciphertext LONGTEXT NOT NULL, iv VARCHAR(64) NOT NULL, auth_tag VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL) ENGINE=InnoDB');
}
function setting(string $name): ?string { $q=db()->prepare('SELECT setting_value FROM app_settings WHERE setting_name=?'); $q->execute([$name]); return $q->fetchColumn() ?: null; }
function derive_key(string $privateKey): string { $salt=base64_decode((string)setting('kdf_salt'),true); if (!$salt) throw new RuntimeException('Jalankan install.php'); return hash_pbkdf2('sha256',$privateKey,$salt,210000,32,true); }
function enc(string $value,string $key): array { $iv=random_bytes(12); $tag=''; $cipher=openssl_encrypt($value,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag); if ($cipher===false) throw new RuntimeException('Enkripsi gagal.'); return ['ciphertext'=>base64_encode($cipher),'iv'=>base64_encode($iv),'auth_tag'=>base64_encode($tag)]; }
function dec(array $row,string $key): string { $value=openssl_decrypt(base64_decode($row['ciphertext']),'aes-256-gcm',$key,OPENSSL_RAW_DATA,base64_decode($row['iv']),base64_decode($row['auth_tag'])); if ($value===false) throw new RuntimeException('Private key tidak valid.'); return $value; }
function e(?string $value): string { return htmlspecialchars($value ?? '',ENT_QUOTES,'UTF-8'); }
function csrf(): string { if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrfok(): void { if(!hash_equals($_SESSION['csrf']??'',$_POST['csrf']??'')) exit('CSRF tidak valid'); }
