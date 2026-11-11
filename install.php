<?php
session_start(); require 'config.php'; $msg='';
try { ensure_database(); $exists=setting('installed_at'); } catch(Throwable $e) { $exists=false; $msg='Tidak dapat menyiapkan database otomatis: '.$e->getMessage(); }
if ($_SERVER['REQUEST_METHOD']==='POST' && !$exists) {
  $privateKey=trim($_POST['private_key']??'');
  if (strlen($privateKey)<12) $msg='Private key minimal 12 karakter.';
  else try {
    $salt=random_bytes(32); $key=hash_pbkdf2('sha256',$privateKey,$salt,210000,32,true);
    db()->beginTransaction();
    $set=db()->prepare('INSERT INTO app_settings(setting_name,setting_value) VALUES(?,?)');
    $set->execute(['kdf_salt',base64_encode($salt)]); $set->execute(['installed_at',date('c')]);
    $insert=db()->prepare('INSERT INTO vault_contents(title,ciphertext,iv,auth_tag,created_at) VALUES(:title,:ciphertext,:iv,:auth_tag,NOW())');
    foreach ([['__verification__','BRANGKAS-VALID'],['Profil Perkara PA-2026-001',"Klasifikasi: Internal Rahasia\nStatus: Dalam pemeriksaan"],['Agenda Sidang Terbatas',"Kamis, 19 November 2026 — 09.00 WIB\nRuang: Sidang Utama"],['Kontak Darurat Institusi',"Koordinator keamanan: +62 812-0000-2026\nsecurity@pa-asril.local"]] as [$title,$content]) { $data=enc($content,$key); $data['title']=$title; $insert->execute($data); }
    db()->commit(); header('Location:admin.php?installed=1'); exit;
  } catch(Throwable $e) { if (db()->inTransaction()) db()->rollBack(); $msg='Inisialisasi gagal: '.$e->getMessage(); }
}
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="assets/style.css"><title>Inisialisasi Brangkas</title></head><body class="dark"><main class="panel"><a class="brand" href="index.php">◆ BRANGKAS</a><h1>Aktifkan Brangkas</h1><p>Langkah pertama dan satu-satunya: buat private key administrator. Database akan disiapkan otomatis.</p><?php if($msg):?><aside><?=e($msg)?></aside><?php endif;?><?php if($exists):?><aside>Sistem sudah aktif. <a href="admin.php">Buka ruang admin</a></aside><?php else:?><form method="post"><label>PRIVATE KEY<input type="password" name="private_key" minlength="12" required autofocus placeholder="Minimal 12 karakter"></label><button class="btn">Aktifkan & Buka Admin</button></form><?php endif;?></main></body></html>
