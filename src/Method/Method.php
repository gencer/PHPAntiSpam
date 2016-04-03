<?php
/**
 * Created by PhpStorm.
 * User: snipe
 * Date: 09.08.15
 * Time: 11:31
 */

namespace PHPAntiSpam\Method;

use PHPAntiSpam\Corpus;
use PHPAntiSpam\DecisionMatrix\DecisionMatrixInterface;
use PHPAntiSpam\Math;

abstract class Method extends Math implements MethodInterface
{
    protected $bias = true;

    protected $text;

    /** @var DecisionMatrixInterface */
    protected $decisionMatrix;

    /** @var  Corpus */
    protected $corpus;

    public function __construct(Corpus $corpus)
    {
        $this->corpus = $corpus;
    }

    public function setBias($bias)
    {
        $this->bias = $bias;
    }


    /**
     * Calculate lexeme value with Paul Graham method.
     *
     * @link http://www.paulgraham.com/spam.html
     *
     * @param $wordSpamCount
     * @param $wordNoSpamCount
     * @param $spamMessagesCount
     * @param $noSpamMessagesCount
     * @return float
     */
    protected function calculateWordValue($wordSpamCount, $wordNoSpamCount, $spamMessagesCount, $noSpamMessagesCount)
    {
        if($spamMessagesCount === 0 || $noSpamMessagesCount === 0) {
            return DecisionMatrixInterface::NEUTRAL;
        }

        $multiplier = 1;

        if ($this->bias) {
            $multiplier = 2;
        }

        $value = ($wordSpamCount / $spamMessagesCount) / (($wordSpamCount / $spamMessagesCount) + (($multiplier * $wordNoSpamCount) / $noSpamMessagesCount));

        return $value;
    }

    protected function setLexemesProbability()
    {
        foreach ($this->corpus->lexemes as $word => $value) {
            $value = $this->calculateWordValue(
                $value['spam'],
                $value['nospam'],
                $this->corpus->messagesCount['spam'],
                $this->corpus->messagesCount['nospam']
            );

            $this->corpus->lexemes[$word]['probability'] = $value;
        }
    }

    protected function getWordsFromText($text)
    {
        $words = array_map(function ($word) {
            return strtolower($word);
        }, $this->corpus->getTokenizer()->tokenize($text));

        return $words;
    }

    abstract protected function setDecisionMatrix($text);
}