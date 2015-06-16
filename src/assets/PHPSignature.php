<?php
namespace Dokra\assets;

class PHPSignature
{
    public $raw;
    public $params = array();

    public function __construct($signature)
    {
        $this->raw = self::getRawFromSignature($signature);
        $this->processParams();
    }

    protected function processParams()
    {
        $previousChar = false;
        $flagInQuotes = false;
        $flagInDoubleQuotes = false;
        $flagInMultilineComment = false;
        $paranthesisLevel = 0;
        $stack = "";

        $listenDefault = false;
        $stackDefault = "";

        for ($i=0; $i < strlen($this->raw); $i++) { 
            $char = $this->raw[$i];

            if ($i > 0) {
                if ($char == "'" && !$flagInDoubleQuotes && !$flagInMultilineComment) {
                    $flagInQuotes = !$flagInQuotes;
                }
                if ($char == '"' && !$flagInQuotes && !$flagInMultilineComment) {
                    $flagInDoubleQuotes = !$flagInDoubleQuotes;
                }
                if ($previousChar == '/' && $char == '*' && !$flagInQuotes && !$flagInDoubleQuotes && !$flagInMultilineComment) {
                    $flagInMultilineComment = true;
                }
                if ($previousChar == '*' && $char == '/' && !$flagInQuotes && !$flagInDoubleQuotes && $flagInMultilineComment) {
                    $flagInMultilineComment = false;
                }
                if ($char == '(' && $paranthesisLevel == 0) {
                    $paranthesisLevel++;
                }
                if ($char == ')' && $paranthesisLevel == 1 && !$flagInQuotes && !$flagInDoubleQuotes && !$flagInMultilineComment) {
                    $paranthesisLevel--;
                }

                if (!$flagInQuotes && !$flagInDoubleQuotes && !$flagInMultilineComment) {
                    if ($char == ')') {
                        $paranthesisLevel--;
                    }
                    if ($char == '(') {
                        $paranthesisLevel++;
                    }
                }

                if ($char == '=' && !$listenDefault) {
                    $listenDefault = true;
                    continue;
                }

                if ($char == ',') {
                    $stack = trim($stack);
                    $stackDefault = trim($stackDefault);
                    $stack = (substr($stack, 0, 1) == '$') ? substr($stack, 1) : $stack;
                    $this->params[$stack] = (empty($stackDefault) && $stackDefault !== 0 && $stackDefault !== '0') ? null : $stackDefault;

                    $stackDefault = "";
                    $stack = "";
                    $listenDefault = false;
                    continue;
                }

                if ($listenDefault) {
                   $stackDefault .= $char;

                } else {
                   $stack .= $char;
                }
            }


            $previousChar = $char;
        }

        $stack = trim($stack);
        $stackDefault = trim($stackDefault);
        $stack = (substr($stack, 0, 1) == '$') ? substr($stack, 1) : $stack;
        if (!empty($stack)) {
            $this->params[$stack] = (empty($stackDefault) && $stackDefault !== 0 && $stackDefault !== '0') ? null : $stackDefault;
        }
    }

    public static function getRawFromSignature($signature)
    {
        $previousChar = false;
        $flagInQuotes = false;
        $flagInDoubleQuotes = false;
        $flagInMultilineComment = false;
        $paranthesisLevel = 0;
        $listen = false;
        $stack = "";

        for ($i=0; $i < strlen($signature); $i++) { 
            $char = $signature[$i];

            if ($i > 0) {
                if ($char == "'" && !$flagInDoubleQuotes && !$flagInMultilineComment) {
                    $flagInQuotes = !$flagInQuotes;
                }
                if ($char == '"' && !$flagInQuotes && !$flagInMultilineComment) {
                    $flagInDoubleQuotes = !$flagInDoubleQuotes;
                }
                if ($previousChar == '/' && $char == '*' && !$flagInQuotes && !$flagInDoubleQuotes && !$flagInMultilineComment) {
                    $flagInMultilineComment = true;
                }
                if ($previousChar == '*' && $char == '/' && !$flagInQuotes && !$flagInDoubleQuotes && $flagInMultilineComment) {
                    $flagInMultilineComment = false;
                }
                if ($char == '(' && $paranthesisLevel == 0) {
                    $listen = true;
                    $paranthesisLevel++;
                    continue;
                }
                if ($char == ')' && $paranthesisLevel == 1 && !$flagInQuotes && !$flagInDoubleQuotes && !$flagInMultilineComment) {
                    $listen = false;
                    $paranthesisLevel--;
                }

                if ($listen) {
                    if (!$flagInQuotes && !$flagInDoubleQuotes && !$flagInMultilineComment) {
                        if ($char == ')') {
                            $paranthesisLevel--;
                        }
                        if ($char == '(') {
                            $paranthesisLevel++;
                        }
                    }

                    $stack .= $char;
                }
            }

            $previousChar = $char;
        }
        return $stack;
    }
}
