<?php

namespace utils\parser\html;

use DOMDocument;
use DOMElement;
use JetBrains\PhpStorm\ExpectedValues;

class HTMLSelector{

    const
        SELECTOR_ID = 'id',
        SELECTOR_CLASS = 'class',
        SELECTOR_TAG = 'tag',
        SELECTOR_XPATH = 'xpath';

    use HTMLHelper;

    private string $selector_type;

    public function __construct(private string $selector){
        if(str_starts_with($this->getSelector(), '#')){
            $this->selector_type = self::SELECTOR_ID;
        }else if(str_starts_with($this->getSelector(), '.')){
            $this->selector_type = self::SELECTOR_CLASS;
        }else if(str_starts_with($this->getSelector(), '//')){
            $this->selector_type = self::SELECTOR_XPATH;
        }else{
            $this->selector_type = self::SELECTOR_TAG;
        }
    }

    public function getSelector(): string{
        return $this->selector;
    }

    /**
     * @return string
     */
    #[ExpectedValues([self::SELECTOR_ID, self::SELECTOR_CLASS, self::SELECTOR_TAG, self::SELECTOR_XPATH])]
    public function getSelectorType(): string{
        return $this->selector_type;
    }

    /**
     * @param DOMDocument $dom
     * @return DOMElement[]
     */
    public function selectAll(DOMDocument $dom): array{
        switch($this->getSelectorType()){
            case self::SELECTOR_ID:
                return array($dom->getElementById(str_replace('#', '', $this->getSelector())));
            case self::SELECTOR_TAG:
                return $this->toDOMElements($dom->getElementsByTagName($this->getSelector()));
            default:
                return array();
        }
    }

    /**
     * @param DOMDocument $dom
     * @return DOMElement|null
     */
    public function selectOne(DOMDocument $dom): ?DOMElement{
        $all = $this->selectAll($dom);
        return !empty($all) ? $this->toDOMElement($all[0]) : null;
    }

}