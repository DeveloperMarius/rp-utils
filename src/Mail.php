<?php

namespace utils;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class Mail{

    /**
     * @var PHPMailer|null $_mailer
     */
    private static ?PHPMailer $_mailer = null;

    /**
     * @param string $subject
     * @param string $body
     * @param string $from
     * @param array|string $recipients
     * @param array $attachments
     * @param MailConfig|null $mail_config
     * @param bool $temp
     * @return bool
     */
    public static function sendMail(string $subject, string $body, string $from, array|string $recipients, array $attachments = array(), array|string $reply_to = array(), ?MailConfig $mail_config = null, bool $temp = false): bool{
        try{
            $from = str_replace(['&amp;', '&quot;', '&#039;', '&apos;'], ['&', '"', '\'', '\''], $from);
            $mail = self::getMailer($from, $mail_config, $temp);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            $mail->isHTML(true);

            if(is_array($reply_to)){
                foreach($reply_to as $reply_to_user){
                    $mail->addReplyTo($reply_to_user);
                }
            }else if(is_string($reply_to)){
                $mail->addReplyTo($reply_to);
            }

            if(is_array($recipients)){
                foreach($recipients as $recipient){
                    $mail->addAddress($recipient);
                }
            }else if(is_string($recipients)){
                $mail->addAddress($recipients);
            }

            foreach($attachments as $attachment){
                if(is_array($attachment)){
                    $mail->addStringAttachment($attachment['data'], $attachment['name'], $attachment['encoding'] ?? PHPMailer::ENCODING_BASE64, $attachment['type'] ?? '', $attachment['disposition'] ?? 'attachment');
                }else{
                    $mail->addAttachment($attachment);
                }
            }
            return $mail->send();
        }catch(Exception $e){
            error_log('INFO: Mail Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param MailConfig|null $mail_config
     * @param bool $temp
     * @return PHPMailer
     * @throws Exception
     */
    private static function getMailer(string $from, ?MailConfig $mail_config = null, bool $temp = false): PHPMailer{
        if (static::$_mailer === null || $temp) {
            if($mail_config === null)
                $mail_config = MailConfig::$default;
            if($mail_config === null)
                throw new Exception('No MailConfig found');
            $mailer = new PHPMailer(true);

            $mailer->isSMTP();
            $mailer->Host = $mail_config->getSmtpHost();
            $mailer->SMTPAuth = true;
            $mailer->Username = $mail_config->getSmtpUser();
            $mailer->Password = $mail_config->getSmtpPassword();
            $mailer->SMTPSecure = $mail_config->getSmtpSecure();
            $mailer->Port = $mail_config->getSmtpPort();
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;

            $mailer->setFrom($mail_config->getSmtpUser(), $from);

            if($temp)
                return $mailer;
            static::$_mailer = $mailer;
        }else{
            static::$_mailer->clearAddresses();
            static::$_mailer->clearAllRecipients();
            static::$_mailer->clearAttachments();
            static::$_mailer->clearBCCs();
            static::$_mailer->clearCCs();
            static::$_mailer->clearCustomHeaders();
            static::$_mailer->clearReplyTos();
        }
        return static::$_mailer;
    }
}