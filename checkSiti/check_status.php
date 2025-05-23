<?php
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

if (isset($_GET['url'])) {
    $site_url = filter_var($_GET['url'], FILTER_VALIDATE_URL);
    if (!$site_url) {
        echo "Offline";
        exit;
    }

    $status = check_site_status($site_url);
    echo $status;

    $log_file = "offline_sites.log";
    $current_time = date("Y-m-d H:i:s");

    if ($status === "Offline") {
        $log_message = "[$current_time] $site_url è Offline\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
}


?>