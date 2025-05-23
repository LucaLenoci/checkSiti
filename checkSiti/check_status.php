<?php
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function check_site_status($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, value: 10);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Segui i redirect
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; WebsiteMonitor/1.0)');

    // Per testare siti HTTPS con certificati self-signed
    // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 200 && $http_code < 400) {
        return "Online";
    } else {
        return "Offline";
    }
}

function send_email_notification($url)
{
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';         // Server SMTP (puoi cambiare se usi altro)
        $mail->SMTPAuth   = true;
        $mail->Username   = 'luca.lenoci.10@gmail.com';      // La tua email
        $mail->Password   = '';            // Password o app password di Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('luca.lenoci.10@gmail.com', 'Monitoraggio Siti');
        $mail->addAddress('lucaxl10@gmail.com');       // Email destinatario

        // Content
        $mail->isHTML(false);
        $mail->Subject = 'Alert: sito offline';
        $mail->Body    = "Il sito $url risulta offline.";

        $mail->send();
        // echo 'Messaggio inviato';
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
    }
}

if (isset($_GET['url'])) {
    $site_url = filter_var($_GET['url'], FILTER_VALIDATE_URL);
    if (!$site_url) {
        echo "Offline";
        exit;
    }

    $status = check_site_status($site_url);
    echo $status;

    $current_time = date("Y-m-d H:i:s");
    $year = (int)date("Y");
    $month = (int)date("n");

    $db = new mysqli("localhost", "root", "", "checkSiti");
    if ($db->connect_error) {
        die("Connessione DB fallita: " . $db->connect_error);
    }

    $stmt = $db->prepare("SELECT id, contatoreDisserviziConsecutivi FROM sitiMonitorati WHERE url = ?");
    $stmt->bind_param("s", $site_url);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $site_id = $row['id'];
        $consec = $row['contatoreDisserviziConsecutivi'];

        if ($status === "Offline") {
            $consec++;

            // Aggiorna disservizi mensili
            $check_stmt = $db->prepare("SELECT contatore FROM disservizi_mensili WHERE idSito = ? AND anno = ? AND mese = ?");
            $check_stmt->bind_param("iii", $site_id, $year, $month);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {
                $update_stmt = $db->prepare("UPDATE disservizi_mensili SET contatore = contatore + 1 WHERE idSito = ? AND anno = ? AND mese = ?");
                $update_stmt->bind_param("iii", $site_id, $year, $month);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                $insert_stmt = $db->prepare("INSERT INTO disservizi_mensili (idSito, anno, mese, contatore) VALUES (?, ?, ?, 1)");
                $insert_stmt->bind_param("iii", $site_id, $year, $month);
                $insert_stmt->execute();
                $insert_stmt->close();
            }
            $check_stmt->close();

            if ($consec == 2) {
                send_email_notification($site_url);
            }
        } else {
            $consec = 0;
        }

        $update = $db->prepare("UPDATE sitiMonitorati SET contatoreDisserviziConsecutivi = ?, ultimoControllo = ? WHERE id = ?");
        $update->bind_param("isi", $consec, $current_time, $site_id);
        $update->execute();
        $update->close();

    } else {
        $consec = ($status === "Offline") ? 1 : 0;

        $insert = $db->prepare("INSERT INTO sitiMonitorati (url, contatoreDisserviziConsecutivi, ultimoControllo) VALUES (?, ?, ?)");
        $insert->bind_param("sis", $site_url, $consec, $current_time);
        $insert->execute();
        $site_id = $insert->insert_id;
        $insert->close();

        if ($status === "Offline") {
            $insert_dis = $db->prepare("INSERT INTO disservizi_mensili (idSito, anno, mese, contatore) VALUES (?, ?, ?, 1)");
            $insert_dis->bind_param("iii", $site_id, $year, $month);
            $insert_dis->execute();
            $insert_dis->close();
        }
    }

    $stmt->close();
    $db->close();
}
?>