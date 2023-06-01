<?php

namespace utils\parser\html;

use DOMDocument;
use DOMElement;
use DOMNodeList;

class HTMLParser{

    use HTMLHelper;

    private DOMDocument $dom;

    public function __construct(private string $html){
       $this->dom = new DOMDocument();
       $this->dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOWARNING);
    }

    /**
     * @return string
     */
    public function getHtml(): string{
        return $this->html;
    }

    /**
     * @param string $html
     */
    public function setHtml(string $html): void{
        $this->html = $html;
    }

    /**
     * @return DOMDocument
     */
    public function getDom(): DOMDocument{
        return $this->dom;
    }

    /**
     * @param DOMElement $element
     * @return array
     */
    public function getClasses(DOMElement $element): array{
        if($element->hasAttribute('class')){
            return explode(' ', $element->getAttribute('class'));
        }
        return array();
    }

    /**
     * @param HTMLSelector|DOMNodeList $element
     * @param string $method
     * @param ...$parameters
     */
    protected function parseToMethod(HTMLSelector|DOMNodeList $element, string $method, ...$parameters){
        if(method_exists($this, $method)){
            if($element instanceof DOMNodeList){
                foreach($this->toDOMElements($element) as $node){
                    $this->$method($node, ...$parameters);
                }
            }else if($element instanceof HTMLSelector){
                foreach($element->selectAll($this->getDom()) as $node){
                    $this->$method($node, ...$parameters);
                }
            }
        }
    }

    /**
     * @return self
     */
    public function saveDom(): self{
        $html = $this->getDom()->saveHTML();
        if($html !== false)
            $this->setHtml($html);
        return $this;
    }

    /**
     * @param HTMLSelector|DOMNodeList|DOMElement $element
     * @param array|string $classes
     * @return self
     */
    public function setClasses(HTMLSelector|DOMNodeList|DOMElement $element, array|string $classes): self{
        if($element instanceof DOMElement){
            if(is_string($classes))
                $classes = array($classes);
            $element->setAttribute('class', join(' ', $classes));
        }else{
            $this->parseToMethod($element, 'setClasses', $classes);
        }
        return $this;
    }

    /**
     * @param HTMLSelector|DOMNodeList|DOMElement $element
     * @param array|string $classes
     * @return self
     */
    public function addClasses(HTMLSelector|DOMNodeList|DOMElement $element, array|string $classes): self{
        if($element instanceof DOMElement){
            if(is_string($classes))
                $classes = array($classes);
            $element_classes = $this->getClasses($element);
            $classes = array_merge($classes, $element_classes);
            $element->setAttribute('class', join(' ', $classes));
        }else{
            $this->parseToMethod($element, 'addClasses', $classes);
        }
        return $this;
    }

}