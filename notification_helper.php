<?php

function ensure_notification_log_table($con)
{
    $sql = "CREATE TABLE IF NOT EXISTS `notification_log` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `u_class` varchar(30) NOT NULL,
        `u_rollno` varchar(30) NOT NULL,
        `channel` varchar(20) NOT NULL,
        `recipient` varchar(255) NOT NULL,
        `status` varchar(30) NOT NULL,
        `response_message` text,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";

    mysqli_query($con, $sql);
}

function log_notification($con, $class, $rollno, $channel, $recipient, $status, $message)
{
    $class = mysqli_real_escape_string($con, $class);
    $rollno = mysqli_real_escape_string($con, $rollno);
    $channel = mysqli_real_escape_string($con, $channel);
    $recipient = mysqli_real_escape_string($con, $recipient);
    $status = mysqli_real_escape_string($con, $status);
    $message = mysqli_real_escape_string($con, $message);

    $sql = "INSERT INTO `notification_log` (`u_class`, `u_rollno`, `channel`, `recipient`, `status`, `response_message`) VALUES ('$class', '$rollno', '$channel', '$recipient', '$status', '$message')";
    mysqli_query($con, $sql);
}

/*
function normalize_mobile($mobile)
{
    $mobile = preg_replace('/[^0-9]/', '', $mobile);
    if (strlen($mobile) > 10) {
        $mobile = substr($mobile, -10);
    }
    return $mobile;
}

function send_sms_fast2sms($mobile, $message, $smsConfig)
{
    if (empty($smsConfig['enabled']) || empty($smsConfig['api_key'])) {
        return array(false, 'SMS disabled or API key missing in notification_config.php');
    }

    $payload = array(
        'route' => isset($smsConfig['route']) ? $smsConfig['route'] : 'v3',
        'sender_id' => isset($smsConfig['sender_id']) ? $smsConfig['sender_id'] : 'FSTSMS',
        'message' => $message,
        'language' => 'english',
        'numbers' => $mobile
    );

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://www.fast2sms.com/dev/bulkV2',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => array(
            'authorization: ' . $smsConfig['api_key'],
            'accept: application/json',
            'content-type: application/json'
        )
    ));

    $response = curl_exec($curl);
    $error = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($error) {
        return array(false, 'SMS CURL error: ' . $error);
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        return array(true, 'SMS sent: ' . $response);
    }

    return array(false, 'SMS API failed (' . $httpCode . '): ' . $response);
}
*/

function send_parent_email($email, $subject, $message, $emailConfig)
{
    if (empty($emailConfig['enabled'])) {
        return array(false, 'Email disabled in notification_config.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return array(false, 'Invalid parent email');
    }

    $from = isset($emailConfig['from']) ? $emailConfig['from'] : 'no-reply@school.local';
    $fromName = isset($emailConfig['from_name']) ? $emailConfig['from_name'] : 'School';

    if (!empty($emailConfig['smtp_enabled'])) {
        return send_parent_email_smtp($email, $subject, $message, $from, $fromName, $emailConfig);
    }

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/plain;charset=UTF-8\r\n";
    $headers .= "From: " . $fromName . " <" . $from . ">\r\n";

    $ok = mail($email, $subject, $message, $headers);
    if ($ok) {
        return array(true, 'Email sent via mail()');
    }

    return array(false, 'PHP mail() failed. Enable SMTP settings in notification_config.php.');
}

function smtp_expect_code($socket, $expectedCode)
{
    $response = '';
    while (($line = fgets($socket, 515)) !== false) {
        $response .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }

    if (strpos($response, (string)$expectedCode) !== 0) {
        return array(false, trim($response));
    }

    return array(true, trim($response));
}

function smtp_send_command($socket, $command, $expectedCode)
{
    fwrite($socket, $command . "\r\n");
    return smtp_expect_code($socket, $expectedCode);
}

function send_parent_email_smtp($toEmail, $subject, $body, $fromEmail, $fromName, $emailConfig)
{
    $host = isset($emailConfig['smtp_host']) ? $emailConfig['smtp_host'] : '';
    $port = isset($emailConfig['smtp_port']) ? (int)$emailConfig['smtp_port'] : 587;
    $encryption = isset($emailConfig['smtp_encryption']) ? strtolower(trim($emailConfig['smtp_encryption'])) : 'tls';
    $username = isset($emailConfig['smtp_username']) ? trim($emailConfig['smtp_username']) : '';
    $password = isset($emailConfig['smtp_password']) ? str_replace(' ', '', trim($emailConfig['smtp_password'])) : '';
    $timeout = isset($emailConfig['smtp_timeout']) ? (int)$emailConfig['smtp_timeout'] : 15;

    if (empty($host) || empty($username) || empty($password)) {
        return array(false, 'SMTP config incomplete: set smtp_host, smtp_username, and smtp_password.');
    }

    $transport = ($encryption === 'ssl') ? 'ssl://' : '';
    $socket = @fsockopen($transport . $host, $port, $errno, $errstr, $timeout);
    if (!$socket) {
        return array(false, 'SMTP connect failed: ' . $errstr . ' (' . $errno . ')');
    }

    stream_set_timeout($socket, $timeout);

    $step = smtp_expect_code($socket, 220);
    if (!$step[0]) {
        fclose($socket);
        return array(false, 'SMTP greeting failed: ' . $step[1]);
    }

    $step = smtp_send_command($socket, 'EHLO localhost', 250);
    if (!$step[0]) {
        fclose($socket);
        return array(false, 'SMTP EHLO failed: ' . $step[1]);
    }

    if ($encryption === 'tls') {
        $step = smtp_send_command($socket, 'STARTTLS', 220);
        if (!$step[0]) {
            fclose($socket);
            return array(false, 'SMTP STARTTLS failed: ' . $step[1]);
        }

        $cryptoOk = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        if ($cryptoOk !== true) {
            fclose($socket);
            return array(false, 'SMTP TLS negotiation failed.');
        }

        $step = smtp_send_command($socket, 'EHLO localhost', 250);
        if (!$step[0]) {
            fclose($socket);
            return array(false, 'SMTP EHLO after TLS failed: ' . $step[1]);
        }
    }

    $step = smtp_send_command($socket, 'AUTH LOGIN', 334);
    if (!$step[0]) {
        fclose($socket);
        return array(false, 'SMTP AUTH LOGIN failed: ' . $step[1]);
    }

    $step = smtp_send_command($socket, base64_encode($username), 334);
    if (!$step[0]) {
        fclose($socket);
        return array(false, 'SMTP username rejected: ' . $step[1]);
    }

    $step = smtp_send_command($socket, base64_encode($password), 235);
    if (!$step[0]) {
        fclose($socket);
        return array(false, 'SMTP password rejected: ' . $step[1]);
    }

    $step = smtp_send_command($socket, 'MAIL FROM:<' . $fromEmail . '>', 250);
    if (!$step[0]) {
        fclose($socket);
        return array(false, 'SMTP MAIL FROM failed: ' . $step[1]);
    }

    $step = smtp_send_command($socket, 'RCPT TO:<' . $toEmail . '>', 250);
    if (!$step[0]) {
        fclose($socket);
        return array(false, 'SMTP RCPT TO failed: ' . $step[1]);
    }

    $step = smtp_send_command($socket, 'DATA', 354);
    if (!$step[0]) {
        fclose($socket);
        return array(false, 'SMTP DATA failed: ' . $step[1]);
    }

    $headers = 'From: ' . $fromName . ' <' . $fromEmail . ">\r\n";
    $headers .= 'To: <' . $toEmail . ">\r\n";
    $headers .= 'Subject: ' . $subject . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";

    fwrite($socket, $headers . $body . "\r\n.\r\n");
    $step = smtp_expect_code($socket, 250);
    if (!$step[0]) {
        fclose($socket);
        return array(false, 'SMTP message rejected: ' . $step[1]);
    }

    smtp_send_command($socket, 'QUIT', 221);
    fclose($socket);

    return array(true, 'Email sent via SMTP');
}

function build_marks_message($studentName, $class, $rollno, $examStatus, $marks)
{
    $firstTotal = $marks['hindi1'] + $marks['english1'] + $marks['math1'] + $marks['physics1'] + $marks['chemestry1'];
    $secondTotal = $marks['hindi2'] + $marks['english2'] + $marks['math2'] + $marks['physics2'] + $marks['chemestry2'];
    $grandTotal = $firstTotal + $secondTotal;

    return "Dear Parent, " . $examStatus . " for " . $studentName . " (Class " . $class . ", Roll " . $rollno . "). " .
        "Half-Yearly: " . $firstTotal . "/500, Annual: " . $secondTotal . "/500, Grand Total: " . $grandTotal . "/1000.";
}

function notify_parent_after_marks($con, $class, $rollno, $marks, $examStatus)
{
    $config = include __DIR__ . '/notification_config.php';
    ensure_notification_log_table($con);

    $classEsc = mysqli_real_escape_string($con, $class);
    $rollEsc = mysqli_real_escape_string($con, $rollno);
    $hasEmailColumn = false;
    $columnRun = mysqli_query($con, "SHOW COLUMNS FROM `student_data` LIKE 'u_email'");
    if ($columnRun && mysqli_num_rows($columnRun) > 0) {
        $hasEmailColumn = true;
    }

    if ($hasEmailColumn) {
        $sql = "SELECT `u_name`, `u_mobile`, `u_email` FROM `student_data` WHERE `u_class`='$classEsc' AND `u_rollno`='$rollEsc' LIMIT 1";
    } else {
        $sql = "SELECT `u_name`, `u_mobile` FROM `student_data` WHERE `u_class`='$classEsc' AND `u_rollno`='$rollEsc' LIMIT 1";
    }
    $run = mysqli_query($con, $sql);

    if (!$run || mysqli_num_rows($run) === 0) {
        return 'Parent record not found, notification skipped.';
    }

    $student = mysqli_fetch_assoc($run);
    $message = build_marks_message($student['u_name'], $class, $rollno, $examStatus, $marks);

    $statusText = array();

    // SMS notifications are intentionally paused for now.
    // Keep these lines disabled until SMS provider setup is completed.
    // $mobile = normalize_mobile($student['u_mobile']);
    // if (!empty($mobile)) {
    //     $smsResult = send_sms_fast2sms($mobile, $message, $config['sms']);
    //     $smsStatus = $smsResult[0] ? 'sent' : 'failed';
    //     log_notification($con, $class, $rollno, 'sms', $mobile, $smsStatus, $smsResult[1]);
    //     $statusText[] = 'SMS: ' . $smsStatus;
    // } else {
    //     $statusText[] = 'SMS: skipped (invalid mobile)';
    // }

    $subject = 'Student Marks Notification - Class ' . $class . ' Roll ' . $rollno;
    $email = isset($student['u_email']) ? trim($student['u_email']) : '';
    if (!empty($email)) {
        $emailResult = send_parent_email($email, $subject, $message, $config['email']);
        $emailStatus = $emailResult[0] ? 'sent' : 'failed';
        log_notification($con, $class, $rollno, 'email', $email, $emailStatus, $emailResult[1]);
        $statusText[] = 'Email: ' . $emailStatus;
    } else {
        $statusText[] = 'Email: skipped (parent email missing)';
    }

    return implode(' | ', $statusText);
}
