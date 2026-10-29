<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=utf-8');
$config = require __DIR__ . '/config.php';
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $config['allowed_origins'], true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($origin, $config['allowed_origins'], true)) {
    http_response_code(403); echo json_encode(['ok'=>false,'message'=>'Request not allowed.']); exit;
}

$data = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($data)) { http_response_code(400); echo json_encode(['ok'=>false,'message'=>'Invalid submission.']); exit; }
if (!empty($data['website'])) { echo json_encode(['ok'=>true,'message'=>'Thank you. Your submission has been received.']); exit; }

$clean = static fn($v, $max=500) => mb_substr(trim(strip_tags((string)$v)), 0, $max);
$type = $clean($data['form_type'] ?? '', 20);
$name = $clean($data['full_name'] ?? '', 120);
$email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
if (!in_array($type, ['application','enquiry'], true) || $name === '' || !$email) {
    http_response_code(422); echo json_encode(['ok'=>false,'message'=>'Please complete all required fields correctly.']); exit;
}
if (($data['consent'] ?? '') !== 'yes') {
    http_response_code(422); echo json_encode(['ok'=>false,'message'=>'Consent is required before submission.']); exit;
}
if ($type === 'application' && (empty($data['phone']) || empty($data['institution']) || empty($data['level']) || empty($data['course']))) {
    http_response_code(422); echo json_encode(['ok'=>false,'message'=>'Please complete all required application fields.']); exit;
}
if ($type === 'enquiry' && (empty($data['subject']) || empty($data['message']))) {
    http_response_code(422); echo json_encode(['ok'=>false,'message'=>'Please enter the subject and your enquiry.']); exit;
}

$labels = ['phone'=>'Phone','institution'=>'Institution','level'=>'Level','course'=>'Course','subject'=>'Subject','message'=>'Message'];
$reference = 'AIMS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
$lines = ['Reference: '.$reference, 'Form: '.ucfirst($type), 'Name: '.$name, 'Email: '.$email];
foreach ($labels as $key=>$label) { if (!empty($data[$key])) $lines[] = $label.': '.$clean($data[$key], $key === 'message' ? 4000 : 250); }

try {
    $directory = dirname($config['database_path']);
    if (!is_dir($directory)) mkdir($directory, 0750, true);
    $db = new PDO('sqlite:' . $config['database_path'], null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec('CREATE TABLE IF NOT EXISTS submissions (id INTEGER PRIMARY KEY AUTOINCREMENT, reference TEXT NOT NULL UNIQUE, form_type TEXT NOT NULL, full_name TEXT NOT NULL, email TEXT NOT NULL, phone TEXT, institution TEXT, level TEXT, course TEXT, subject TEXT, message TEXT, status TEXT NOT NULL DEFAULT "New", created_at TEXT NOT NULL)');
    $db->exec('CREATE TABLE IF NOT EXISTS rate_limits (ip_hash TEXT NOT NULL, created_at INTEGER NOT NULL)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_rate_limits_ip_time ON rate_limits(ip_hash, created_at)');
    $ipHash=hash('sha256',(string)($_SERVER['REMOTE_ADDR']??'unknown'));
    $cutoff=time()-600;$db->prepare('DELETE FROM rate_limits WHERE created_at < ?')->execute([$cutoff]);
    $rate=$db->prepare('SELECT COUNT(*) FROM rate_limits WHERE ip_hash=? AND created_at>=?');$rate->execute([$ipHash,$cutoff]);
    if ((int)$rate->fetchColumn()>=5) { http_response_code(429); echo json_encode(['ok'=>false,'message'=>'Too many submissions. Please wait before trying again.']); exit; }
    $db->prepare('INSERT INTO rate_limits (ip_hash,created_at) VALUES (?,?)')->execute([$ipHash,time()]);
    $stmt = $db->prepare('INSERT INTO submissions (reference, form_type, full_name, email, phone, institution, level, course, subject, message, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$reference,$type,$name,$email,$clean($data['phone']??'',50),$clean($data['institution']??'',200),$clean($data['level']??'',100),$clean($data['course']??'',150),$clean($data['subject']??'',200),$clean($data['message']??'',4000),date(DATE_ATOM)]);
} catch (Throwable $e) {
    error_log('AIMS submission storage error: '.$e->getMessage());
    http_response_code(500); echo json_encode(['ok'=>false,'message'=>'Your submission could not be recorded. Please try again.']); exit;
}

require __DIR__ . '/vendor/autoload.php';
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_username'];
    $mail->Password = $config['smtp_password'];
    $mail->SMTPSecure = $config['smtp_encryption'];
    $mail->Port = (int)$config['smtp_port'];
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($config['smtp_username'], 'AIMS Nigeria Website');
    $mail->addAddress($config['recipient']);
    $mail->addReplyTo((string)$email, $name);
    $mail->Subject = $type === 'application' ? 'New AIMS Programme Application' : 'New AIMS Website Enquiry';
    $mail->Body = implode("\n\n", $lines);
    $mail->send();
    $confirmation = new PHPMailer(true);
    $confirmation->isSMTP(); $confirmation->Host=$config['smtp_host']; $confirmation->SMTPAuth=true; $confirmation->Username=$config['smtp_username']; $confirmation->Password=$config['smtp_password']; $confirmation->SMTPSecure=$config['smtp_encryption']; $confirmation->Port=(int)$config['smtp_port']; $confirmation->CharSet='UTF-8';
    $confirmation->setFrom($config['smtp_username'], 'AIMS Nigeria'); $confirmation->addAddress((string)$email, $name);
    $confirmation->Subject = 'AIMS submission received - '.$reference;
    $confirmation->Body = "Dear {$name},\n\nThank you for contacting AIMS Nigeria. Your submission has been received.\n\nReference: {$reference}\nType: ".ucfirst($type)."\n\nPlease keep this reference for follow-up. The AIMS team will contact you using the details provided.\n\nAssociation for Information and Management Science of Nigeria\ncontact@aimsnigeria.org.ng";
    $confirmation->send();
    echo json_encode(['ok'=>true,'reference'=>$reference,'message'=>'Submission received. Your reference is '.$reference.'. A confirmation has been sent to your email.']);
} catch (Exception $e) {
    error_log('AIMS form mail error: '.$mail->ErrorInfo);
    http_response_code(500); echo json_encode(['ok'=>false,'message'=>'We could not send your form. Please email contact@aimsnigeria.org.ng.']);
}
