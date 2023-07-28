<?php

namespace utils;

class PasswordGenerator{

    /**
     * @var bool $use_special_characters
     */
    private bool $use_special_characters = false;
    /**
     * @var string[] $special_characters_data
     * "§" is missing, because it breaks the password
     */
    private array $special_characters_data = array(
        '?', '!', '_', '-', '=', ')', '(', '{', '}', '[', ']', ';', ',', '.', '"', '/', '\\', '@', '#', '%', '$', '&', '^', '*', '~', '\'', '<', '>'
    );
    /**
     * @var bool $use_numbers
     */
    private bool $use_numbers = false;
    /**
     * @var int[] $numbers_data
     */
    private array $numbers_data = array(
        0, 1, 2, 3, 4, 5, 6, 7, 8, 9
    );
    /**
     * @var bool $use_lower_case_letters
     */
    private bool $use_lower_case_letters = true;
    /**
     * @var string[] $lower_case_letter_data
     */
    private array $lower_case_letter_data = array(
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'
    );
    /**
     * @var bool $use_upper_case_letters
     */
    private bool $use_upper_case_letters = false;
    /**
     * @var string[] $upper_case_letter_data
     */
    private array $upper_case_letter_data = array(
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
    );
    /**
     * @var bool $start_with_letter
     */
    private bool $start_with_letter = false;

    /**
     * PasswordGenerator constructor.
     * @param int $length
     */
    public function __construct(private int $length = 8){}

    /**
     * @return string
     */
    public function generate(): string{
        $chars = '';
        if($this->isUseLowerCaseLetters())
            $chars .= implode($this->lower_case_letter_data);
        if($this->isUseUpperCaseLetters())
            $chars .= implode($this->upper_case_letter_data);
        if($this->isUseNumbers())
            $chars .= implode($this->numbers_data);
        if($this->isUseSpecialCharacters())
            $chars .= implode($this->special_characters_data);
        $password = '';
        $passwordLength = $this->getLength();
        if($this->isStartWithLetter() && ($this->isUseLowerCaseLetters() || $this->isUseUpperCaseLetters())){
            $chars_start = '';
            if($this->isUseLowerCaseLetters())
                $chars_start .= implode($this->lower_case_letter_data);
            if($this->isUseUpperCaseLetters())
                $chars_start .= implode($this->upper_case_letter_data);
            $password .= $this->generateString(1, $chars_start);
            $passwordLength--;
        }
        $charsLength = strlen($chars) - 1;
        for ($i = 0; $i < $passwordLength; $i++) {
            $n = mt_rand(0, $charsLength);
            $password .= $chars[$n];
        }
        return $password;
    }

    /**
     * @param int $length
     * @param string $chars
     * @return string
     */
    private function generateString(int $length, string $chars): string{
        $string = '';
        $charsLength = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $n = mt_rand(0, $charsLength);
            $string .= $chars[$n];
        }
        return $string;
    }

    /* Settings */

    /**
     * @return int
     */
    public function getLength(): int{
        return $this->length;
    }

    /**
     * @param int $length
     * @return PasswordGenerator
     */
    public function setLength(int $length): self{
        $this->length = $length;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUseSpecialCharacters(): bool{
        return $this->use_special_characters;
    }

    /**
     * @param bool $use_special_characters
     * @return PasswordGenerator
     */
    public function setUseSpecialCharacters(bool $use_special_characters): self{
        $this->use_special_characters = $use_special_characters;
        return $this;
    }

    /**
     * @param string[] $special_characters_data
     * @return PasswordGenerator
     */
    public function setSpecialCharactersData(array $special_characters_data): self{
        $this->special_characters_data = $special_characters_data;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUseNumbers(): bool{
        return $this->use_numbers;
    }

    /**
     * @param bool $use_numbers
     * @return PasswordGenerator
     */
    public function setUseNumbers(bool $use_numbers): self{
        $this->use_numbers = $use_numbers;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUseLowerCaseLetters(): bool{
        return $this->use_lower_case_letters;
    }

    /**
     * @param bool $use_lower_case_letters
     * @return PasswordGenerator
     */
    public function setUseLowerCaseLetters(bool $use_lower_case_letters): self{
        $this->use_lower_case_letters = $use_lower_case_letters;
        return $this;
    }

    /**
     * @return bool
     */
    public function isUseUpperCaseLetters(): bool{
        return $this->use_upper_case_letters;
    }

    /**
     * @param bool $use_upper_case_letters
     * @return PasswordGenerator
     */
    public function setUseUpperCaseLetters(bool $use_upper_case_letters): self{
        $this->use_upper_case_letters = $use_upper_case_letters;
        return $this;
    }

    /**
     * @return bool
     */
    public function isStartWithLetter(): bool{
        return $this->start_with_letter;
    }

    /**
     * @param bool $start_with_letter
     * @return PasswordGenerator
     */
    public function setStartWithLetter(bool $start_with_letter): self{
        $this->start_with_letter = $start_with_letter;
        return $this;
    }

}