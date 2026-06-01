<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

function trimiteEmail($catre, $nume_destinatar, $subiect, $continut) {
    $mail = new PHPMailer(true);

    try {
        // Fix pentru IPv6 (uneori Windows încearcă să se conecteze prin IPv6 și eșuează)
        $mail->Host = gethostbyname('smtp.gmail.com');
        
        $mail->isSMTP();
        $mail->SMTPAuth   = true;
        
        // --- DATELE TALE AICI ---
        $mail->Username   = 'marinescustefan04@gmail.com';  // <-- PUNE MAILUL TĂU
        $mail->Password   = 'huzh yeee gxjz gcof'; // <-- PUNE CODUL TĂU
        
        // --- MODIFICAREA PRINCIPALĂ AICI (SSL pe Port 465) ---
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        // --- FIX PENTRU LOCALHOST ---
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('marinescustefan04@gmail.com', 'PC Shop'); // <-- PUNE MAILUL TĂU
        $mail->addAddress($catre, $nume_destinatar);

        $mail->isHTML(true);
        $mail->Subject = $subiect;
        $mail->Body    = $continut;
        $mail->AltBody = strip_tags($continut);

        $mail->send();
        return true;
    } catch (Exception $e) {
        echo "<div style='background:red;color:white;padding:10px;'>Eroare Tehnică: {$mail->ErrorInfo}</div>";
        return false;
    }
}
?>