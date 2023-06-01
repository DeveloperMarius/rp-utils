<?php

namespace utils\parser\html;

use DOMElement;
use DOMNode;
use DOMNodeList;
use JetBrains\PhpStorm\Pure;

trait HTMLHelper{

    /**
     * @param DOMNode $node
     * @return DOMElement|null
     */
    public function toDOMElement(DOMNode $node): ?DOMElement{
        if($node instanceof DOMElement){
            return $node;
        }
        return null;
    }

    /**
     * @param DOMNodeList $nodes
     * @return DOMElement[]
     */
    #[Pure]
    public function toDOMElements(DOMNodeList $nodes): array{
        $result = array();
        foreach($nodes as $node){
            $node = $this->toDOMElement($node);
            if($node !== null)
                $result[] = $node;
        }
        return $result;
    }
}