<?php
/**
 * @Author shaowei
 * @Date   2015-12-01
 */

namespace src\common;

require_once LIBS_DIR . '/phpmailer/class.phpmailer.php';
require_once LIBS_DIR . '/phpmailer/class.smtp.php';

class SendMail
{
    const SMTP_USER     = 'noreply@xxx.com';
    const SMTP_PASSWD   = 'xxx'; // TODO
    const SMTP_PORT     = 465;
    const SMTP_HOST     = 'smtp.exmail.qq.com';
    const SMTP_SECURE   = 'ssl';

    const MAIL_FROM     = 'noreply@xxx.com';

    public static function sendmail($toList, $subject, $content)
    {
        $mail = new \PHPMailer;
        $mail->isSMTP();
        $mail->Host = self::SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = self::SMTP_USER;
        $mail->Password = self::SMTP_PASSWD;
        $mail->SMTPSecure = self::SMTP_SECURE;
        $mail->Port = self::SMTP_PORT;
        $mail->Timeout = 3; // seconds

        $mail->From = self::MAIL_FROM;
        $mail->CharSet = 'utf-8';
        $mail->FromName = 'xxx'; // TODO
        foreach ($toList as $to) {
            $mail->addAddress($to);
        }

        //$mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $content;
        $mail->AltBody = $content;

        if (!$mail->send()) {
            Log::fatal('mailer error: ' . $mail->ErrorInfo);
            return false;
        }
        return true;
    }
}

