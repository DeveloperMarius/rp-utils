<?php

namespace utils;

use JetBrains\PhpStorm\Deprecated;
use JetBrains\PhpStorm\Pure;
use PHPMailer\PHPMailer\PHPMailer;

#[Deprecated]
class mail_config{

    /**
     * @var mail_config|null $default - Default mail_config
     */
    public static ?mail_config $default = null;

    /**
     * @var string $smtp_user
     */
    private string $smtp_user;
    /**
     * @var string $smtp_password
     */
    private string $smtp_password;
    /**
     * @var string $email_from_name
     */
    private string $email_from_name;
    /**
     * @var string $smtp_host
     */
    private string $smtp_host;
    /**
     * @var int $smtp_port
     */
    private int $smtp_port;
    /**
     * @var string $smtp_secure
     */
    private string $smtp_secure;
    /**
     * @var string $mail_color_link
     */
    private string $mail_color_link = '#fdae55';
    /**
     * @var string|null $logo_light with "data:image/png;base64,..."
     */
    private ?string $logo_light = null;
    /**
     * @var string|null $logo_dark with "data:image/png;base64,..."
     */
    private ?string $logo_dark = null;
    /**
     * @var int $logo_width
     */
    private int $logo_width = 150;
    /**
     * @var int $logo_height
     */
    private int $logo_height = 38;

    /**
     * MailConfig constructor.
     * @param string $smtp_user
     * @param string $smtp_password
     * @param string $email_from_name
     * @param string $smtp_host
     * @param string $smtp_secure
     * @param int $smtp_port
     */
    public function __construct(string $smtp_user, string $smtp_password, string $email_from_name, string $smtp_host = 'mail.repaste.de', int $smtp_port = 25, string $smtp_secure = PHPMailer::ENCRYPTION_STARTTLS){
        $this->smtp_user = $smtp_user;
        $this->smtp_password = $smtp_password;
        $this->email_from_name = $email_from_name;
        $this->smtp_host = $smtp_host;
        $this->smtp_port = $smtp_port;
        $this->smtp_secure = $smtp_secure;
    }

    /**
     * @return string
     */
    #[Pure]
    public function getSmtpUser(): string{
        return $this->smtp_user;
    }

    /**
     * @return string
     */
    #[Pure]
    public function getSmtpPassword(): string{
        return $this->smtp_password;
    }

    /**
     * @return string
     */
    #[Pure]
    public function getEmailFromName(): string{
        return $this->email_from_name;
    }

    /**
     * @return string
     */
    #[Pure]
    public function getEmailFromEmail(): string{
        return $this->getSmtpUser();
    }

    /**
     * @return string
     */
    #[Deprecated(
        reason: 'Use getEmailFromName() instead',
        replacement: '%class%->getEmailFromName()'
    )]
    #[Pure]
    public function getEmailHeader(): string{
        return $this->getEmailFromName();
    }

    /**
     * @return string
     */
    public function getSmtpHost(): string{
        return $this->smtp_host;
    }

    /**
     * @return int
     */
    public function getSmtpPort(): int{
        return $this->smtp_port;
    }

    /**
     * @return string
     */
    public function getSmtpSecure(): string{
        return $this->smtp_secure;
    }

    /**
     * @param string $mail_color_link
     */
    public function setMailColorLink(string $mail_color_link): void{
        $this->mail_color_link = $mail_color_link;
    }

    /**
     * @return string
     */
    public function getMailColorLink(): string{
        return $this->mail_color_link;
    }

    /**
     * @return bool
     */
    public function hasLogo(): bool{
        return $this->getLogoLight() !== null || $this->getLogoDark() !== null;
    }

    /**
     * @return string|null
     */
    public function getLogoDark(): ?string{
        return $this->logo_dark;
    }

    /**
     * @param string $logo_dark
     */
    public function setLogoDark(string $logo_dark): void{
        $this->logo_dark = $logo_dark;
    }

    /**
     * @return string|null
     */
    public function getLogoLight(): ?string{
        return $this->logo_light;
    }

    /**
     * @param string $logo_light
     */
    public function setLogoLight(string $logo_light): void{
        $this->logo_light = $logo_light;
    }

    /**
     * @return int
     */
    public function getLogoWidth(): int{
        return $this->logo_width;
    }

    /**
     * @param int $logo_width
     */
    public function setLogoWidth(int $logo_width): void{
        $this->logo_width = $logo_width;
    }

    /**
     * @return int
     */
    public function getLogoHeight(): int{
        return $this->logo_height;
    }

    /**
     * @param int $logo_height
     */
    public function setLogoHeight(int $logo_height): void{
        $this->logo_height = $logo_height;
    }

}