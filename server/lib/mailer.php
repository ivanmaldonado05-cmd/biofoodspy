<?php
/* Envío de correo.
   - Si existe PHPMailer (recomendado, vía SMTP), lo usa.
   - Si no, usa la función mail() de PHP con MIME (respaldo).
   $to: string o array de direcciones. $attachment: ruta a archivo (opcional). */
require_once __DIR__ . '/helpers.php';

function send_mail($to, $subject, $html, $attachment = null, $attachmentName = null) {
  $c = cfg();
  $recipients = is_array($to) ? $to : [$to];
  $fromEmail = $c['mail_from'];
  $fromName  = $c['mail_from_name'];

  // ---- 1) PHPMailer (SMTP) si está disponible y configurado ----
  $autoload = __DIR__ . '/PHPMailer/src/PHPMailer.php';
  if (!empty($c['smtp']['host']) && file_exists($autoload)) {
    require_once __DIR__ . '/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/src/SMTP.php';
    try {
      $m = new PHPMailer\PHPMailer\PHPMailer(true);
      $m->isSMTP();
      $m->Host = $c['smtp']['host'];
      $m->Port = $c['smtp']['port'];
      $m->SMTPAuth = true;
      $m->Username = $c['smtp']['user'];
      $m->Password = $c['smtp']['pass'];
      $m->SMTPSecure = $c['smtp']['secure'];
      $m->CharSet = 'UTF-8';
      $m->setFrom($fromEmail, $fromName);
      foreach ($recipients as $r) { $m->addAddress($r); }
      $m->isHTML(true);
      $m->Subject = $subject;
      $m->Body = $html;
      $m->AltBody = strip_tags($html);
      if ($attachment && file_exists($attachment)) { $m->addAttachment($attachment, $attachmentName ?: basename($attachment)); }
      return $m->send();
    } catch (Throwable $e) { /* cae al respaldo */ }
  }

  // ---- 2) Respaldo: mail() con MIME ----
  $boundary = 'bf_' . md5(uniqid('', true));
  $headers  = "From: {$fromName} <{$fromEmail}>\r\n";
  $headers .= "Reply-To: {$fromEmail}\r\n";
  $headers .= "MIME-Version: 1.0\r\n";

  if ($attachment && file_exists($attachment)) {
    $data = chunk_split(base64_encode(file_get_contents($attachment)));
    $fname = $attachmentName ?: basename($attachment);
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n{$html}\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: application/octet-stream; name=\"{$fname}\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"{$fname}\"\r\n\r\n{$data}\r\n";
    $body .= "--{$boundary}--";
  } else {
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body = $html;
  }

  $ok = true;
  foreach ($recipients as $r) { $ok = mail($r, $subject, $body, $headers) && $ok; }
  return $ok;
}
