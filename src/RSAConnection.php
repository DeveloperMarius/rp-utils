<?php

namespace utils;

use JetBrains\PhpStorm\ArrayShape;

class RSAConnection{

    /**
     * @var string $public_key
     */
    private string $public_key;
    /**
     * @var string $private_key
     */
    private string $private_key;

    public function __construct(){
    }

    /**
     * @param bool $clean
     * @return string
     */
    public function getPublicKey($clean = true): string{
        return (!$clean ? "-----BEGIN PUBLIC KEY-----\n" : "") . $this->public_key . (!$clean ? "\n-----END PUBLIC KEY-----" : "");
    }

    /**
     * @param string $public_key
     * @param bool $clean
     */
    public function setPublicKey(string $public_key, $clean = true): void{
        if(!$clean){
            $public_key = str_replace("-----BEGIN PUBLIC KEY-----\n", "", $public_key);
            $public_key = str_replace("\n-----END PUBLIC KEY-----\n", "", $public_key);
        }
        $this->public_key = $public_key;
    }

    /**
     * @param bool $clean
     * @return string
     */
    public function getPrivateKey($clean = true): string{
        return (!$clean ? "-----BEGIN PRIVATE KEY-----\n" : "") . $this->private_key . (!$clean ? "\n-----END PRIVATE KEY-----" : "");
    }

    /**
     * @param string $private_key
     * @param bool $clean
     */
    public function setPrivateKey(string $private_key, $clean = true): void{
        if(!$clean){
            $private_key = str_replace("-----BEGIN PRIVATE KEY-----\n", "", $private_key);
            $private_key = str_replace("\n-----END PRIVATE KEY-----\n", "", $private_key);
        }
        $this->private_key = $private_key;
    }

    public function createKeys(){
        $config = array(
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        );
        $res = openssl_pkey_new($config);
        if($res !== false){
            openssl_pkey_export($res, $private_key_pem, null, $config);
            $this->setPrivateKey($private_key_pem, false);

            $details = openssl_pkey_get_details($res);
            $this->setPublicKey($details['key'], false);
        }else{
            echo openssl_error_string();
        }
    }

    /**
     * @param string $data
     * @return string
     */
    public function sign(string $data): string{
        openssl_sign($data, $signature, $this->getPrivateKey(false), OPENSSL_ALGO_SHA256);
        return $signature;
    }

    /**
     * @param string|null $data - default is php input
     * @return mixed
     */
    public function parseData(?string $data = null): mixed{
        if($data === null)
            $data = file_get_contents('php://input');
        return json_decode($data, true)['data'];
    }

    /**
     * @param string|null $data - default is php input
     * @param string|null $signature - default is header
     * @param int $expire_time - default is 60 sec
     * @return bool
     */
    public function verify(?string $data = null, ?string $signature = null, int $expire_time = 30): bool{
        if($data === null)
            $data = file_get_contents('php://input');
        if($signature === null){
            $signature = $_SERVER['HTTP_SIGNATURE'];
            if($signature === null)
                return false;
        }
        $signature = base64_decode($signature);
        $public_key = $this->getPublicKey(false);
        $public_key = openssl_pkey_get_public($public_key);
        if(openssl_verify($data, $signature, $public_key, OPENSSL_ALGO_SHA256) == 1){
            $data = json_decode($data, true);
            if($data['sent_time']+1000*$expire_time > Util::getTimestamp() && (isset($data['sent_ip']) ? $data['sent_ip'] == Util::getUserIp() : true) && (isset($data['sent_user_agent']) ? $data['sent_user_agent'] == Util::getBrowser() : true)){
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $target
     * @param mixed $data
     * @param bool $follow
     * @return bool|string
     */
    public function connect(string $target, mixed $data, bool $follow = false): bool|string{
        $data = json_encode(array(
            'sent_time' => Util::getTimestamp(),
            //'sent_ip' => Util::getUserIp(),
            'data' => $data
        ));
        $signature = $this->sign($data);

        $con = curl_init();
        curl_setopt($con, CURLOPT_URL, $target);
        curl_setopt($con, CURLOPT_POST, true);
        curl_setopt($con, CURLOPT_POSTFIELDS, $data);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        if($follow)
            curl_setopt($con, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($con, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'signature: ' . base64_encode($signature)
        ));
        $output = curl_exec($con);
        curl_close($con);
        return $output;
    }

    #[ArrayShape([
        'SIGNATURE' => "string",
        'REQUEST_DATA' => "string"
    ])]
    public function getRedirectHeaders(mixed $data): array{
        $data = json_encode(array(
            'sent_time' => Util::getTimestamp(),
            'data' => $data
        ));
        $signature = $this->sign($data);
        return array(
            'SIGNATURE' => base64_encode($signature),
            'REQUEST_DATA' => base64_encode($data)
        );
    }

    #[ArrayShape([
        'signature' => "string",
        'data' => "string"
    ])]
    public function getRedirectData(mixed $data): array{
        $data = json_encode(array(
            'sent_time' => Util::getTimestamp(),
            'sent_ip' => Util::getUserIP(),
            'sent_user_agent' => Util::getBrowser(),
            'data' => $data
        ));
        $signature = $this->sign($data);
        return array(
            'signature' => base64_encode($signature),
            'data' => base64_encode($data)
        );
    }

}
