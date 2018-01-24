<?php
namespace core;

/**
 * Generates semantic strings based on the 1st chapter of the book 1984
 * @author Jason Wright <jason@silvermast.io>
 * @since 1/28/17
 * @package charon
 */
class SemanticString {

    /** @var array */
    private $_index = [];

    public function __construct($filepath) {

        if (!filter_var($filepath, FILTER_VALIDATE_URL) && !file_exists($filepath))
            throw new \Exception("File does not exist or is not a URL: $filepath");

        if (!$contents = file_get_contents($filepath))
            throw new \Exception("Unable to read file/url: $filepath");

        // list of words delineated by newline
        $this->_index = preg_split("/\s+/mis", $contents);
    }

    /**
     * Retrieves a random word from the text file
     * @return string
     */
    public function getRandomWord() {
        return $this->_index[array_rand($this->_index)];
    }

    /**
     * Random 2-6 if null
     * @param null $word_count
     * @return string
     */
    public function getSemanticString($word_count = null) {
        $words      = [];
        $word_count = $word_count ?? mt_rand(2, 6);

        for ($w = 1; $w <= $word_count; $w++)
            $words[] = $this->getRandomWord();

        return implode(' ', $words);
    }

}