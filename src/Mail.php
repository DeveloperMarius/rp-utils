<?php

namespace utils;

use JetBrains\PhpStorm\Pure;
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
     * @param string|array $recipients
     * @param array $attachments
     * @param bool $build_body
     * @param mail_config|null $mail_config
     * @param bool $temp
     * @return bool
     */
    public static function sendMail(string $subject, string $body, array|string $recipients, array $attachments = array(), bool $build_body = true, ?mail_config $mail_config = null, bool $temp = false): bool{
        try{
            $body = $build_body ? self::buildMailBody($body) : $body;

            $mail = self::getMailer($mail_config, $temp);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            $mail->isHTML(true);

            if(is_array($recipients)){
                foreach($recipients as $recipient){
                    $mail->addAddress($recipient);
                }
            }else if(is_string($recipients)){
                $mail->addAddress($recipients);
            }

            foreach($attachments as $attachment){
                if(is_array($attachment)){
                    $mail->addStringAttachment($attachment['data'], $attachment['name']);
                }else{
                    $mail->addAttachment($attachment);
                }
            }
            return $mail->send();
        }catch(Exception $e){
            return false;
        }
    }

    /**
     * @param string $text
     * @param string $link
     * @param string $color
     * @param string $classNames
     * @param int $radius
     * @param int $height - font height + padding
     * @return string
     *
     * Generate a button: https://buttons.cm/
     */
    #[Pure]
    public static function buildMailButton(string $text, string $link, string $color = '#fdae55', string $classNames = 'button', int $radius = 4, int $height = 18): string{
        $radius = ceil(($radius * 100) / $height);
        return <<<HTML
<!--[if mso]>
    <br>
  <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{$link}" style="height:{$height}px;width:100%;v-text-anchor:middle;" arcsize="{$radius}%" stroke="f" fillcolor="{$color}">
    <w:anchorlock/>
    <center>
  <![endif]--><a class="{$classNames}" href="{$link}" style="color: {$color};">{$text}</a><!--[if mso]>
    </center>
  </v:roundrect>
<![endif]-->
HTML;
    }

    //Only outlook.com -> [owa] .foo { color:red; }

    /**
     * @param string $body
     * @param string $preview
     * @param string $logo_tag
     * @return string
     */
    public static function buildMailBody(string $body, string $preview = '', string $logo_tag = '%LOGO%'): string{
        $mail_config =  mail_config::$default;
        $link_color = $mail_config->getMailColorLink();
        $logo_style = '';
        if($mail_config->hasLogo()){
            if($mail_config->getLogoDark() !== null && $mail_config->getLogoLight() !== null){
                $logo_style = '.logo{ margin-top: 20px; margin-bottom: 10px; background: url(' . $mail_config->getLogoDark() . ') no-repeat left center; background-size: contain; width: ' . $mail_config->getLogoWidth() . 'px; height: ' . $mail_config->getLogoHeight() . 'px; }';
                $logo_style .= '@media (prefers-color-scheme: dark) { .logo{ background: url(' . $mail_config->getLogoLight() . ') no-repeat left center;} body, .body, .content a.button{ background-color: #161d31; } table.body-table{ background-color: #283046; } .body:not(.force_light) h1, .body:not(.force_light) h2, .body:not(.force_light) p, .body:not(.force_light) span, .body:not(.force_light) b, .body:not(.force_light) a.sm-icon { color: #ffffff !important; } }';
                $logo_style .= '[data-ogsc] .logo{ background: url(' . $mail_config->getLogoLight() . ') no-repeat left center;} [data-ogsc] body, [data-ogsc] .body, [data-ogsc] .content a.button{ background-color: #161d31; } [data-ogsc] table.body-table{ background-color: #283046; } [data-ogsc] .body:not(.force_light) h1, [data-ogsc] .body:not(.force_light) h2, [data-ogsc] .body:not(.force_light) p, [data-ogsc] .body:not(.force_light) span, [data-ogsc] .body:not(.force_light) b, [data-ogsc] .body:not(.force_light) a.sm-icon { color: #ffffff !important; } }';
            }else if($mail_config->getLogoDark() !== null || $mail_config->getLogoLight() !== null){
                $logo_style = '.logo{ margin-top: 20px; margin-bottom: 10px; background: url(' . ($mail_config->getLogoDark() !== null ? $mail_config->getLogoDark() : $mail_config->getLogoLight()) . ') no-repeat left center; background-size: contain; width: ' . $mail_config->getLogoWidth() . 'px; height: ' . $mail_config->getLogoHeight() . 'px; }';
            }
        }
        $body = <<<EOT
<!DOCTYPE html>
<html lang="de" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
<style>
    .body{
        background-color: #f7fafc;
        margin: 0;
    }
    .content{
        font-size: 14px; 
        vertical-align: top;
    }
    .content, .content p, .content a, .content span{
        font-family: sans-serif;
        font-size: 16px;
        font-weight: normal;
        margin-bottom: 15px;
        margin-top: 15px;
        color: #4f5a68;
    }
    .content p.heading{
        font-weight: bold;
        margin-top: 10px;
    }
    .content a{
        text-decoration: none;
        color: $link_color !important;
    }
    .content p.small{
        font-size: 12px;
        color: #8492a6 !important;
    }
    p.small a{
        font-size: 12px;
    }
    .content a.button{
        border-radius: 4px;
        background-color: $link_color;
        font-size: 15px;
        color: #fff !important;
        padding: 15px 25px !important;
        display: inline-block;
    }
    table.body-table{
        margin: 0 auto;
        padding: 0 20px;
        max-width: 580px;
        background: #fff;
    }
    hr{
        border-top: 1px solid #f7fafc;
        margin-top: 35px;
    }
    a[x-apple-data-detectors=true] {
        color: inherit !important;
        text-decoration: none !important;
    }
    $logo_style
</style>
<meta charset="UTF-8" />
<meta name="x-apple-disable-message-reformatting">
<meta http-equiv="x-ua-compatible" content="ie=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
<meta name="color-scheme" content="light dark">
<meta name="supported-color-schemes" content="light dark">
<title>$preview</title>
<!--[if mso]>
<xml>
    <o:OfficeDocumentSettings>
        <o:AllowPNG/>
        <o:PixelsPerInch>96</o:PixelsPerInch>
    </o:OfficeDocumentSettings>
</xml>
<style>
    td, th, div, p, a, h1, h2, h3, h4, h5, h6 {
        font-family: "Segoe UI", sans-serif;
        mso-line-height-rule: exactly;
    }
</style>
<![endif]-->
</head>
<body>
<table class="body" width="100%" cellspacing="0" cellpadding="40" border="0">
<tbody>
<tr>
    <td>
    <span style="color: transparent; display: none; height: 0; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; mso-hide: all; visibility: hidden; width: 0;">$preview</span>
    <table class="body-table" style="border-collapse: separate; width: 580px;">
        <tbody>
        <tr>
            <td class="content">
                $body
            </td>
        </tr>
        </tbody>
    </table>
    </td>
    </tr>
    </tbody>
</table>
</body>
</html>
EOT;
        $body = str_replace($logo_tag, <<<HTML
<div class="logo">
<!--[if gte mso 9]>
<img src="{$mail_config->getLogoDark()}" width="{$mail_config->getLogoWidth()}" height="{$mail_config->getLogoHeight()}" style="width: {$mail_config->getLogoWidth()}px; height: {$mail_config->getLogoHeight()}px;">
<![endif]-->
</div>
HTML, $body);
        return $body;
    }

    /**
     * @param mail_config|null $mail_config
     * @param bool $temp
     * @return PHPMailer
     * @throws Exception
     */
    private static function getMailer(?mail_config $mail_config = null, bool $temp = false): PHPMailer{
        if (static::$_mailer === null || $temp) {
            if($mail_config === null)
                $mail_config = mail_config::$default;
            $mailer = new PHPMailer(true);

            $mailer->isSMTP();
            $mailer->Host = $mail_config->getSmtpHost();
            $mailer->SMTPAuth = true;
            $mailer->Username = $mail_config->getSmtpUser();
            $mailer->Password = $mail_config->getSmtpPassword();
            $mailer->SMTPSecure = $mail_config->getSmtpSecure();
            $mailer->Port = $mail_config->getSmtpPort();
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;

            $mailer->setFrom($mail_config->getSmtpUser(), $mail_config->getEmailFromName());

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