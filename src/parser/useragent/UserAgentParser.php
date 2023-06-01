<?php

namespace utils\parser\useragent;

/**
 * Lightweight integration to check client.
 * For more detailed information use https://github.com/ua-parser/uap-php
 */
class UserAgentParser{

    private ?string $client = null;
    private ?string $client_version = null;
    private ?string $os = null;
    private ?string $os_version = null;

    public function __construct(private string $userAgent){}

    /**
     * @return string
     */
    public function getUserAgent(): string{
        return $this->userAgent;
    }

    /**
     * @return string
     */
    public function getClient(): string{
        if($this->client === null){
            //Client
            $client = null;
            if(str_contains($this->getUserAgent(), 'Brave')){
                if(str_contains($this->getUserAgent(), 'Mobile')){
                    return 'brave mobile';
                }else{
                    return 'brave';
                }
            }else if(str_contains($this->getUserAgent(), 'Ecosia')){
                return 'ecosia mobile';
            }else if(str_contains($this->getUserAgent(), 'SamsungBrowser')){
                return 'samsung browser mobile';
            }else if(str_contains($this->getUserAgent(), 'DuckDuckGo')){
                return 'duckduckgo mobile';
            }else if(str_contains($this->getUserAgent(), 'Firefox/')){
                if(str_contains($this->getUserAgent(), 'Mobile')){
                    $client = 'firefox mobile';
                }else{
                    $client = 'firefox';
                }
            }else if(str_contains($this->getUserAgent(), 'Edge/')){
                $client = 'edge';
            }else if(str_contains($this->getUserAgent(), 'EdgA/')){
                $client = 'edge mobile';
            }else if(str_contains($this->getUserAgent(), 'OPR/')){
                if(str_contains($this->getUserAgent(), 'Mobile Safari')){
                    $client = 'opera mobile';
                }else{
                    $client = 'opera';
                }
            }else if(str_contains($this->getUserAgent(), 'Safari/')){
                if(str_contains($this->getUserAgent(), 'Applebot/')){
                    $client = 'applebot';
                }else if(str_contains($this->getUserAgent(), 'Google-Publisher-Plugin')){
                    $client = 'google-publisher-plugin';
                }else if(str_contains($this->getUserAgent(), 'Princetonbot/')){
                    $client = 'princetonbot';
                }else if(str_contains($this->getUserAgent(), 'translate.google.com')){
                    $client = 'google translate';
                }else{
                    if(str_contains($this->getUserAgent(), 'Chrome/')){
                        if(str_contains($this->getUserAgent(), 'Instagram')){
                            $client = 'instagram';
                        }else if(str_contains($this->getUserAgent(), 'Mobile Safari/')){
                            $client = 'chrome mobile';
                        }else{
                            $client = 'chrome';
                        }
                    }else{
                        if(str_contains($this->getUserAgent(), 'Mobile/')){
                            $client = 'safari mobile';
                        }else{
                            $client = 'safari';
                        }
                    }
                }
            }else if(str_contains($this->getUserAgent(), 'Trident/')){
                $client = 'internet explorer';
            }else if(str_contains($this->getUserAgent(), 'Instagram')){
                $client = 'instagram';
            }else if(str_contains($this->getUserAgent(), 'Outlook')){
                $client = 'outlook';
            }else{
                $client = 'unknown';
            }
            $this->client = htmlspecialchars($client);
        }
        return $this->client;
    }

    /**
     * @return string
     */
    public function getClientTitle(): string{
        return match($this->getClient()){
            'brave' => 'Brave',
            'brave mobile' => 'Brave Handy',

            'ecosia mobile' => 'Ecosia Handy',

            'samsung browser mobile' => 'SamsungBrowser Handy',

            'duckduckgo mobile' => 'DuckDuckGo Handy',

            'firefox' => 'Firefox',
            'firefox mobile' => 'Firefox Handy',

            'edge' => 'Edge',
            'edge mobile' => 'Edge Handy',

            'opera' => 'Opera',
            'opera mobile' => 'Opera Handy',

            'chrome' => 'Chrome',
            'chrome mobile' => 'Chrome Handy',

            'safari' => 'Safari',
            'safari mobile' => 'Safari Handy',

            'internet explorer' => 'Internet Explorer',

            'instagram' => 'Instagram',
            'outlook' => 'Outlook',

            default => $this->getClient()
        };
    }

    /**
     * @return string|null
     */
    public function getOS(): ?string{
        if($this->os === null){
            $matches = null;
            preg_match('/\((.*?);.*\)/', $this->getUserAgent(), $matches);
            $this->os = sizeof($matches) === 2 ? htmlspecialchars($matches[1]) : 'Unbekannt';
        }
        return $this->os;
    }

}