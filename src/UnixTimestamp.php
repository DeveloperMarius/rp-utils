<?php

namespace utils;

class UnixTimestamp extends Time{

    public function toString(): string{
        return strval($this->getMilliseconds(true));
    }

    public function jsonSerialize(): mixed{
        return $this->getMilliseconds(true);
    }

}