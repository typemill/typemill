<?php

namespace Typemill\Models;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Typemill\Static\Translations;

class Mail
{
    public $error;

    private string $from     = '';
    private string $fromName = '';
    private string $replyTo  = '';
    private array  $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;

        if (isset($settings['mailfrom']) && $settings['mailfrom'] !== '')
        {
            $this->from = trim($settings['mailfrom']);
        }

        if (isset($settings['mailfromname']) && $settings['mailfromname'] !== '')
        {
            $this->fromName = trim($settings['mailfromname']);
        }

        // Fix: form field is 'replyto', SimpleMail incorrectly read 'mailreply'
        if (isset($settings['replyto']) && $settings['replyto'] !== '')
        {
            $this->replyTo = trim($settings['replyto']);
        }
    }

    public function send(string $to, string $subject, string $message): bool
    {
        if ($this->from === '')
        {
            $this->error = Translations::translate('Email address in system settings is missing.');
            return false;
        }

        $mail = new PHPMailer(true);

        try
        {
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom($this->from, $this->fromName);

            if ($this->replyTo !== '')
            {
                $mail->addReplyTo($this->replyTo);
            }

            if (isset($this->settings['mailsmtp']) && $this->settings['mailsmtp'])
            {
                date_default_timezone_set('Etc/UTC');

                $mail->isSMTP();
                $mail->Host      = isset($this->settings['mailhost']) ? $this->settings['mailhost'] : '';
                $mail->Port      = isset($this->settings['mailport']) ? (int) $this->settings['mailport'] : 587;
                $mail->SMTPDebug = 0;

                if (isset($this->settings['mailsmtpsecure']) && $this->settings['mailsmtpsecure'] !== '')
                {
                    $mail->SMTPSecure = $this->settings['mailsmtpsecure'];
                }

                if (isset($this->settings['mailsmtpauth']) && $this->settings['mailsmtpauth'])
                {
                    $mail->SMTPAuth = true;

                    if (isset($this->settings['mailusername']) && $this->settings['mailusername'] !== '')
                    {
                        $mail->Username = $this->settings['mailusername'];
                    }

                    // Read SMTP password from secrets.yaml (never stored in plain settings.yaml)
                    $settingsModel  = new Settings();
                    $smtpPassword   = $settingsModel->getSecret('mailpassword');

                    if ($smtpPassword)
                    {
                        $mail->Password = $smtpPassword;
                    }
                }
            }
            else
            {
                $mail->isMail();
            }

            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $message;
            $mail->AltBody = strip_tags($message);

            $mail->send();

            return true;
        }
        catch (Exception $e)
        {
            $this->error = $mail->ErrorInfo;
            return false;
        }
    }
}
