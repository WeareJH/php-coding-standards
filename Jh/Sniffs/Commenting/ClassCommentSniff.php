<?php

/**
 * @author Aydin Hassan <aydin@wearejh.com>
 */
class Jh_Sniffs_Commenting_ClassCommentSniff implements PHP_CodeSniffer_Sniff
{
    private $requiredAttributes = [
        '@author'
    ];

    public function register()
    {
        return [
            T_CLASS,
            T_INTERFACE
        ];
    }

    public function process(PHP_CodeSniffer_File $phpCsFile, $stackPtr)
    {
        $tokens = $phpCsFile->getTokens();
        $find   = PHP_CodeSniffer_Tokens::$methodPrefixes;
        $find[] = T_WHITESPACE;

        $commentEnd = $phpCsFile->findPrevious($find, $stackPtr - 1, null, true);
        if ($tokens[$commentEnd]['code'] !== T_DOC_COMMENT_CLOSE_TAG
            && $tokens[$commentEnd]['code'] !== T_COMMENT
        ) {
            $phpCsFile->addError('Missing class doc comment', $stackPtr, 'Missing');
            $phpCsFile->recordMetric($stackPtr, 'Class has doc comment', 'no');
            return;
        }

        $phpCsFile->recordMetric($stackPtr, 'Class has doc comment', 'yes');

        if ($tokens[$commentEnd]['code'] === T_COMMENT) {
            $phpCsFile->addError('You must use "/**" style comments for a class comment', $stackPtr, 'WrongStyle');
            return;
        }

        if ($tokens[$commentEnd]['line'] !== ($tokens[$stackPtr]['line'] - 1)) {
            $error = 'There must be no blank lines after the class comment';
            $phpCsFile->addError($error, $commentEnd, 'SpacingAfter');
        }

        $commentStart = $tokens[$commentEnd]['comment_opener'];

        $start = $commentStart;
        while (false !== ($commentString = $phpCsFile->findNext(T_DOC_COMMENT_STRING, $start, $commentEnd))) {
            $value = $tokens[$commentString]['content'];

            if (strpos($value, 'Class ') === 0) {
                $error = 'Class doc string not allowed - change your PHP Storm doc templates ;)';
                $phpCsFile->addError($error, $commentStart, 'ClassNotAllowed', [$value]);
            }

            $start = $commentString + 1;
        }

        $tags = $tokens[$commentStart]['comment_tags'];
        $tagLabels = [];
        foreach ($tags as $tag) {
            $tagLabels[$tag] = $tokens[$tag]['content'];
        }

        foreach ($tags as $tag) {
            $tagLabel = $tagLabels[$tag];
            if (!in_array($tagLabel, $this->requiredAttributes, true)) {
                $error = '%s tag is not allowed in class comment';
                $data  = array($tokens[$tag]['content']);
                $phpCsFile->addError($error, $tag, 'TagNotAllowed', $data);
            }
        }

        foreach ($this->requiredAttributes as $requiredAttribute) {
            if (!in_array($requiredAttribute, $tagLabels, true)) {
                $error = '%s tag must be in class comment';
                $phpCsFile->addError($error, $commentStart, 'TagRequired', [$requiredAttribute]);
                continue;
            }

            $pointer = array_search($requiredAttribute, $tagLabels, true);
            $content = $tokens[$pointer + 2]['content'];

            $validateMethod = 'validate' . ucfirst(substr($requiredAttribute, 1));
            if (method_exists($this, $validateMethod) && !$this->$validateMethod($content)) {
                $error = 'The value for %s tag is not valid: %s';
                $phpCsFile->addError($error, $pointer + 2, 'TagNotValid', [$requiredAttribute, $content]);
            }
        }
    }

    private function validateAuthor($value)
    {
        $res = preg_match('/^(?P<name>[- .,\p{L}\p{N}\'’"()]+) <(?P<email>.+?)>$/u', $value, $matches);

        if (!$res) {
            return false;
        }

        return false !== filter_var($matches['email'], FILTER_VALIDATE_EMAIL);
    }
}