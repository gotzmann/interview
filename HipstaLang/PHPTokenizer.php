<?php

/*
    PHP Tokenizer in pure PHP

    (c) 2018 / Serge Gotsuliak / MIT License
    Based on JS version written by Marco Marchiò and Brett Zamir

    Split given source into PHP tokens exactly as standard function token_get_all
    NB! Token numbers depend on the PHP version so don't treat them as constant

    Example usage:

    $code = '<?php echo "Hello World!" ?>';
    $tokens = (new PHPTokenizer())->token_get_all($code);

*/

class PHPTokenizer {

    public static function token_name ($code) {
        foreach (self::$tokens as $key => $value)
            if ($code == $value)
                return $key;
        return "[NO-TOKEN-REPRESENTATION]";
    }

    public function token_get_all ($source) {

        $this->source = $source;
        $this->length = mb_strlen($source);

        for ($this->i = 0; $this->i < $this->length; $this->i++) {

            $ch = $this->source[$this->i];
            $ascii = ord($ch);

            // Whitespaces

            if ($this->isWhitespace($ascii)) {
                //Get next whitespaces too
                $ch .= $this->getWhitespaces($this->i);
                $this->emitToken($ch, self::$tokens['T_WHITESPACE']);
                $this->line += $this->countNewLines($ch);

            } else if ($ch == '#' || ($ch == '/' && (($nextch = $this->source[$this->i + 1]) == '*' || $nextch == '/'))) {

                // Comment signs
                // Change the buffer only if there's no active buffer

                if (!$this->bufferType) {
                    if ($ch == "#") {
                        $this->getBufferAndEmit("#", "inlineComment", "\n");
                    } else if ($ch == '/' && $nextch == "/") {
                        $this->getBufferAndEmit("//", "inlineComment", "\n");
                    } else if (($ch == '/' && $nextch == '*' && $this->source[$this->i + 2]) == "*") {
                        $this->getBufferAndEmit(
                            "/**",
                            //It's a doc comment only if it's followed by a whitespaces
                            $this->isWhitespace($this->source[$this->i + 3]) ? "docComment" : "comment",
                            "*/"
                        );
                    } else {
                        $this->getBufferAndEmit("/*", "comment", "*/");
                    }
                    continue;
                }

                $this->emitToken($ch);

            } else if (($ch == '$') && ($word = $this->getWord($this->i + 1))) {

                // Variable

                if (($this->bufferType == "heredoc" || $this->bufferType == "doubleQuotes") && !$this->isEscaped()) {
                    $this->splitString();
                    $this->emitToken($ch . $word, self::$tokens['T_VARIABLE'], true);
                } else {
                    $this->emitToken($ch . $word, self::$tokens['T_VARIABLE']);
                }

                $this->i += mb_strlen($word);

            } else if ($ch == "<" && mb_substr($this->source,$this->i + 1, 2) == "<<" && ($word = $this->getHeredocWord())) {

                // Heredoc and nowdoc start declaration
                $this->emitToken($word[0], self::$tokens['T_START_HEREDOC']);
                $this->line++;

                if (!$this->bufferType) {
                    $this->heredocWord = $word[1];

                    // If the first character is a quote then it's a nowdoc otherwise it's an heredoc
                    if ($this->heredocWord[0] == "'") {

                        // Strip the leading quote
                        $this->heredocWord = mb_substr($this->heredocWord, 1);
                        $this->bufferType = "nowdoc";

                    } else {
                        if ($this->heredocWord[0] == '"') {
                            $this->heredocWord = mb_substr($this->heredocWord, 1);
                        }
                        $this->bufferType = "heredoc";
                    }
                    $this->lineBuffer = $this->line;
                }
                $this->i += mb_strlen($word[0]) - 1;

            } else if ($ch == "(" && ($word = $this->getTypeCasting())) {

                // Type-casting
                $this->emitToken($word[0], $this->typeCasting[$word[1].toLowerCase()]);
                $this->i += mb_strlen($word[0]) - 1;
            } else if (($ch == "." || ($ch >= "0" && $ch <= "9")) && ($num = $this->getNumber())) {

                // Numbers
                // Numeric array index inside a heredoc or a double quoted string
                if ($this->lastToken == "[" && ($this->bufferType == "heredoc" || $this->bufferType == "doubleQuotes")) {
                    $this->emitToken($num[0], self::$tokens['T_NUM_STRING'], true);
                } else {
                    $this->emitToken($num[0], $num[1]);
                }
                $this->i += mb_strlen(strval($num[0])) - 1;

           } else if (mb_strpos(self::$singleTokenChars, $ch) !== false) {

                // Symbols

                $sym = mb_substr($this->source, $this->i, 3);
                if (array_key_exists($sym, self::$symbols3chars)) {
                    $this->i += 2;

                    // If it's a php open tag emit the html buffer
                    if ($this->bufferType == "html" && $this->symbols3chars[$sym] == self::$tokens['T_OPEN_TAG_WITH_ECHO'])
                        $this->emitBuffer();

                    $this->emitToken($sym, self::$symbols3chars[$sym]);
                    continue;
                }

                $sym = $ch . $this->source[$this->i + 1];
                if (array_key_exists($sym, self::$symbols2chars)) {

                    // If it's a php open tag check if it's written in the long form and emit the html buffer

                    if (self::$symbols2chars[$sym] == self::$tokens['T_OPEN_TAG'] && $this->bufferType == "html") {
                        $this->emitBuffer();
                        $this->i++;
                        if ($word = $this->getLongOpenDeclaration($this->i + 1)) {
                            $this->i += mb_strlen($word[0]);
                            $sym .= $word[0];

                        }
                        $this->emitToken($sym, self::$tokens['T_OPEN_TAG']);
                        if (mb_strpos($sym, "\n") !== false)
                            $this->line++;

                        continue;
                    }
                    $this->i++;

                    // Syntax $obj->prop inside strings and heredoc

                    if ($sym == "->" && $this->lastToken == self::$tokens['T_VARIABLE'] &&
                       ($this->bufferType == "heredoc" || $this->bufferType == "doubleQuotes")) {
                        $this->emitToken($sym, self::$symbols2chars[$sym], true);
                        continue;
                    }

                    $this->emitToken($sym, self::$symbols2chars[$sym]);

                    // If the token is a PHP close tag and there isn't an active buffer start an html buffer

                    if (!$this->bufferType && self::$symbols2chars[$sym] == self::$tokens['T_CLOSE_TAG']) {

                        // PHP closing tag includes the following new line characters

                        preg_match("/^\r?\n/", mb_substr($this->source, $this->i + 1, 2), $match);
                        if ($nextch = $match[0]) {
                            $this->ret[count($this->ret) - 1][1] .= $nextch[0];
                            $this->i += mb_strlen($nextch[0]);
                            $this->line++;
                        }

                        $this->bufferType = "html";
                        $this->lineBuffer = $this->line;
                    }
                    continue;
                }

                // Start string buffers if there isn't an active buffer and the character is a quote

                if (!$this->bufferType && ($ch == "'" || $ch == '"')) {
                    if ($ch == "'") {
                        $this->getBufferAndEmit("'", "singleQuote", "'", true);
                    } else {
                        $this->split = false;
                        $this->bufferType = "doubleQuotes";
                        $this->lineBuffer = $this->line;

                        // Add the token to the buffer and continue to skip next checks
                        $this->emitToken($ch);
                    }

                    continue;

                } else if ($ch == '"' && $this->bufferType == "doubleQuotes" && !$this->isEscaped()) {

                    // If the string has been splitted emit the current buffer and the double quotes as separate tokens

                    if ($this->split) {
                        $this->splitString();
                        $this->bufferType = null;
                        $this->emitToken('"');
                    } else {
                        $this->emitToken('"');
                        $this->emitBuffer();
                    }

                    continue;
                } else if ($this->bufferType == "heredoc" || $this->bufferType == "doubleQuotes") {

                    // Array index delimiters inside heredoc or double quotes

                    if (($ch == "[" && $this->lastToken == self::$tokens['T_VARIABLE']) ||
                            ($ch == "]" && ($this->lastToken == self::$tokens['T_NUM_STRING'] ||
                            $this->lastToken == self::$tokens['T_STRING']))) {
                        $this->emitToken($ch, null, true);
                        continue;

                } else if ((($ch == "$" && $this->source[$this->i + 1] == "{") ||
                                ($ch == "{" && $this->source[$this->i + 1] == "$")) &&
                                !$this->isEscaped()) {

                        // Complex variable syntax ${varname} or {$varname}. Store the current
                        // buffer type and evaluate next tokens as there's no active buffer.
                        // The current buffer will be reset when the declaration is closed

                        $this->splitString();
                        $this->complexVarPrevBuffer = $this->bufferType;
                        $this->bufferType = null;
                        if ($ch == "$") {
                            $this->emitToken($ch . "{", self::$tokens['T_DOLLAR_OPEN_CURLY_BRACES']);
                            $this->i++;
                        } else {
                            $this->emitToken($ch, self::$tokens['T_CURLY_OPEN']);
                        }
                        $this->openBrackets = 1;
                        continue;
                    }

                } else if ($ch == "\\") {

                    // Namespace separator
                    $this->emitToken($ch, self::$tokens['T_NS_SEPARATOR']);
                    continue;
                }
                $this->emitToken($ch);

                // Increment or decrement the number of open brackets inside a complex variable syntax

                if ($this->complexVarPrevBuffer && ($ch == "{" || $ch == "}")) {
                    if ($ch == "{") {
                        $this->openBrackets++;
                    } else if (!--$this->openBrackets) {

                        //If every bracket has been closed reset the previous buffer
                        $this->bufferType = $this->complexVarPrevBuffer;
                        $this->complexVarPrevBuffer = null;
                    }
                }

             } else if ($word = $this->getWord($this->i)) {

                // Words

                $wordLower = strtolower($word);

                // Check to see if it's a keyword

                if (array_key_exists($word, self::$keywordsToken) || array_key_exists($wordLower, self::$keywordsToken)) {

                    // If it's preceded by -> than it's an object property and it must be tokenized as T_STRING

                    if ($this->lastToken == self::$tokens['T_OBJECT_OPERATOR'])
                        $emit = self::$tokens['T_STRING'];
                    else
                        $emit = self::$keywordsToken[$word] ? self::$keywordsToken[$word] : self::$keywordsToken[$wordLower];

                    $this->emitToken($word, $emit);
                    $this->i += mb_strlen($word) - 1;

                    continue;
                }

                // Stop the heredoc or the nowdoc if it's the word that has generated it

                if (($this->bufferType == "nowdoc" || $this->bufferType == "heredoc") &&
                        $word == $this->heredocWord &&
                        $this->source[$i - 1] == "\n" &&
                        $this->heredocEndFollowing.test(source.substr(i + word.length))) {

                    $this->emitBuffer();
                    $this->emitToken($word, self::$tokens['T_END_HEREDOC']);
                    $this->i += mb_strlen($word) - 1;
                    continue;

                } else if (($this->bufferType == "heredoc" || $this->bufferType == "doubleQuotes")) {

                    if ($this->lastToken == "[") {

                        // Literal array index inside a heredoc or a double quoted string
                        $this->emitToken($word, self::$tokens['T_STRING'], true);
                        $this->i += mb_strlen($word) - 1;
                        continue;

                    } else if ($this->lastToken == self::$tokens['T_OBJECT_OPERATOR']) {

                        // Syntax $obj->prop inside strings and heredoc
                        $this->emitToken($word, self::$tokens['T_STRING'], true);
                        $this->i += mb_strlen($word) - 1;
                        continue;
                    }

                } else if ($this->complexVarPrevBuffer && $this->lastToken == self::$tokens['T_DOLLAR_OPEN_CURLY_BRACES']) {

                    // Complex variable syntax  ${varname}
                    $this->emitToken($word, self::$tokens['T_STRING_VARNAME']);
                    $this->i += mb_strlen($word) - 1;
                    continue;
                }

                $this->emitToken($word, self::$tokens['T_STRING']);
                $this->i += mb_strlen($word) - 1;

           } else if ($ascii < 32) {

                // If below ascii 32 it's a bad character
                $this->emitToken($ch, self::$tokens['T_BAD_CHARACTER']);

            } else {

                // If there isn't an open buffer there should be an syntax error, but we don't care
                // so it will be emitted as a simple string
                $this->emitToken($ch, self::$tokens['T_STRING']);

            }
        }

        // If there's an open buffer emit it
        if ($this->bufferType && ($this->bufferType !== "doubleQuotes" || !$this->split)) {
            $this->emitBuffer();
        } else {
            $this->splitString();
        }

        return $this->ret;
    } // token_get_all

    // Token to number conversion

    private $source, $length, $i;

    // Characters that are emitted as tokens without a code
    private static $singleTokenChars = ";(){}[],~@`=+/-*.$|^&<>%!?:\"'\\";

    // Buffer type. Start an html buffer immediatelly.
    private $bufferType = "html";
    // private $bufferType = null; // FIXME! Try this first

    // Buffer content
    private $buffer = "";

    // Last emitted token
    private $lastToken = null;

    // Results array
    private $ret = [];

    // Word that started the heredoc or nowdoc buffer
    private $heredocWord = null;

    // Line number
    private $line = 1;

    // Line at which the buffer begins
    private $lineBuffer = 1;

    // Flag that indicates if the current double quoted string has been splitted
    private $split = null;

    // This variable will store the previous buffer type of the tokenizer before parsing a
    // complex variable syntax
    private $complexVarPrevBuffer = null;

    // Number of open brackets inside a complex variable syntax
    private $openBrackets = null;

    // Regexp to check if the characters that follow a word are valid as heredoc end declaration
    private $heredocEndFollowing = "/^;?\r?\n/";

    private static $tokens = [
        'T_REQUIRE_ONCE' => 261,
        'T_REQUIRE' => 260,
        'T_EVAL' => 259,
        'T_INCLUDE_ONCE' => 258,
        'T_INCLUDE' => 257,
        'T_LOGICAL_OR' => 262,
        'T_LOGICAL_XOR' => 263,
        'T_LOGICAL_AND' => 264,
        'T_PRINT' => 265,
        'T_SR_EQUAL' => 276,
        'T_SL_EQUAL' => 275,
        'T_XOR_EQUAL' => 274,
        'T_OR_EQUAL' => 273,
        'T_AND_EQUAL' => 272,
        'T_MOD_EQUAL' => 271,
        'T_CONCAT_EQUAL' => 270,
        'T_DIV_EQUAL' => 269,
        'T_MUL_EQUAL' => 268,
        'T_MINUS_EQUAL' => 267,
        'T_PLUS_EQUAL' => 266,
        'T_BOOLEAN_OR' => 277,
        'T_BOOLEAN_AND' => 278,
        'T_IS_NOT_IDENTICAL' => 282,
        'T_IS_IDENTICAL' => 281,
        'T_IS_NOT_EQUAL' => 280,
        'T_IS_EQUAL' => 279,
        'T_IS_GREATER_OR_EQUAL' => 284,
        'T_IS_SMALLER_OR_EQUAL' => 283,
        'T_SR' => 286,
        'T_SL' => 285,
        'T_INSTANCEOF' => 287,
        'T_UNSET_CAST' => 296,
        'T_BOOL_CAST' => 295,
        'T_OBJECT_CAST' => 294,
        'T_ARRAY_CAST' => 293,
        'T_STRING_CAST' => 292,
        'T_DOUBLE_CAST' => 291,
        'T_INT_CAST' => 290,
        'T_DEC' => 289,
        'T_INC' => 288,
        'T_CLONE' => 298,
        'T_NEW' => 297,
        'T_EXIT' => 299,
        'T_IF' => 300,
        'T_ELSEIF' => 301,
        'T_ELSE' => 302,
        'T_ENDIF' => 303,
        'T_LNUMBER' => 304,
        'T_DNUMBER' => 305,
        'T_STRING' => 306,
        'T_STRING_VARNAME' => 307,
        'T_VARIABLE' => 308,
        'T_NUM_STRING' => 309,
        'T_INLINE_HTML' => 310,
        'T_CHARACTER' => 311,
        'T_BAD_CHARACTER' => 312,
        'T_ENCAPSED_AND_WHITESPACE' => 313,
        'T_CONSTANT_ENCAPSED_STRING' => 314,
        'T_ECHO' => 315,
        'T_DO' => 316,
        'T_WHILE' => 317,
        'T_ENDWHILE' => 318,
        'T_FOR' => 319,
        'T_ENDFOR' => 320,
        'T_FOREACH' => 321,
        'T_ENDFOREACH' => 322,
        'T_DECLARE' => 323,
        'T_ENDDECLARE' => 324,
        'T_AS' => 325,
        'T_SWITCH' => 326,
        'T_ENDSWITCH' => 327,
        'T_CASE' => 328,
        'T_DEFAULT' => 329,
        'T_BREAK' => 330,
        'T_CONTINUE' => 331,
        'T_GOTO' => 332,
        'T_FUNCTION' => 333,
        'T_CONST' => 334,
        'T_RETURN' => 335,
        'T_TRY' => 336,
        'T_CATCH' => 337,
        'T_THROW' => 338,
        'T_USE' => 339,
        'T_GLOBAL' => 340,
        'T_PUBLIC' => 346,
        'T_PROTECTED' => 345,
        'T_PRIVATE' => 344,
        'T_FINAL' => 343,
        'T_ABSTRACT' => 342,
        'T_STATIC' => 341,
        'T_VAR' => 347,
        'T_UNSET' => 348,
        'T_ISSET' => 349,
        'T_EMPTY' => 350,
        'T_HALT_COMPILER' => 351,
        'T_CLASS' => 352,
        'T_INTERFACE' => 353,
        'T_EXTENDS' => 354,
        'T_IMPLEMENTS' => 355,
        'T_OBJECT_OPERATOR' => 356,
        'T_DOUBLE_ARROW' => 357,
        'T_LIST' => 358,
        'T_ARRAY' => 359,
        'T_CLASS_C' => 360,
        'T_METHOD_C' => 361,
        'T_FUNC_C' => 362,
        'T_LINE' => 363,
        'T_FILE' => 364,
        'T_COMMENT' => 365,
        'T_DOC_COMMENT' => 366,
        'T_OPEN_TAG' => 367,
        'T_OPEN_TAG_WITH_ECHO' => 368,
        'T_CLOSE_TAG' => 369,
        'T_WHITESPACE' => 370,
        'T_START_HEREDOC' => 371,
        'T_END_HEREDOC' => 372,
        'T_DOLLAR_OPEN_CURLY_BRACES' => 373,
        'T_CURLY_OPEN' => 374,
        'T_PAAMAYIM_NEKUDOTAYIM' => 375,
        'T_NAMESPACE' => 376,
        'T_NS_C' => 377,
        'T_DIR' => 378,
        'T_NS_SEPARATOR' => 379
    ];

    // Keywords tokens

    private static $keywordsToken;
    private static $typeCasting;
    private static $symbols2chars;
    private static $symbols3chars;
    private static $bufferTokens;

    public function __construct() {

    self::$keywordsToken = [
        'abstract' => self::$tokens['T_ABSTRACT'],
        'array' => self::$tokens['T_ARRAY'],
        'as' => self::$tokens['T_AS'],
        'break' => self::$tokens['T_BREAK'],
        'case' => self::$tokens['T_CASE'],
        'catch' => self::$tokens['T_CATCH'],
        'class' => self::$tokens['T_CLASS'],
        '__CLASS__' => self::$tokens['T_CLASS_C'],
        'clone' => self::$tokens['T_CLONE'],
        'const' => self::$tokens['T_CONST'],
        'continue' => self::$tokens['T_CONTINUE'],
        'declare' => self::$tokens['T_DECLARE'],
        'default' => self::$tokens['T_DEFAULT'],
        '__DIR__' => self::$tokens['T_DIR'],
        'die' => self::$tokens['T_EXIT'],
        'do' => self::$tokens['T_DO'],
        'echo' => self::$tokens['T_ECHO'],
        'else' => self::$tokens['T_ELSE'],
        'elseif' => self::$tokens['T_ELSEIF'],
        'empty' => self::$tokens['T_EMPTY'],
        'enddeclare' => self::$tokens['T_ENDDECLARE'],
        'endfor' => self::$tokens['T_ENDFOR'],
        'endforeach' => self::$tokens['T_ENDFOREACH'],
        'endif' => self::$tokens['T_ENDIF'],
        'endswitch' => self::$tokens['T_ENDSWITCH'],
        'endwhile' => self::$tokens['T_ENDWHILE'],
        'eval' => self::$tokens['T_EVAL'],
        'exit' => self::$tokens['T_EXIT'],
        'extends' => self::$tokens['T_EXTENDS'],
        '__FILE__' => self::$tokens['T_FILE'],
        'final' => self::$tokens['T_FINAL'],
        'for' => self::$tokens['T_FOR'],
        'foreach' => self::$tokens['T_FOREACH'],
        'function' => self::$tokens['T_FUNCTION'],
        '__FUNCTION__' => self::$tokens['T_FUNC_C'],
        'global' => self::$tokens['T_GLOBAL'],
        'goto' => self::$tokens['T_GOTO'],
        '__halt_compiler' => self::$tokens['T_HALT_COMPILER'],
        'if' => self::$tokens['T_IF'],
        'implements' => self::$tokens['T_IMPLEMENTS'],
        'include' => self::$tokens['T_INCLUDE'],
        'include_once' => self::$tokens['T_INCLUDE_ONCE'],
        'instanceof' => self::$tokens['T_INSTANCEOF'],
        'interface' => self::$tokens['T_INTERFACE'],
        'isset' => self::$tokens['T_ISSET'],
        '__LINE__' => self::$tokens['T_LINE'],
        'list' => self::$tokens['T_LIST'],
        'and' => self::$tokens['T_LOGICAL_AND'],
        'or' => self::$tokens['T_LOGICAL_OR'],
        'xor' => self::$tokens['T_LOGICAL_XOR'],
        '__METHOD__' => self::$tokens['T_METHOD_C'],
        'namespace' => self::$tokens['T_NAMESPACE'],
        '__NAMESPACE__' => self::$tokens['T_NS_C'],
        'new' => self::$tokens['T_NEW'],
        'print' => self::$tokens['T_PRINT'],
        'private' => self::$tokens['T_PRIVATE'],
        'public' => self::$tokens['T_PUBLIC'],
        'protected' => self::$tokens['T_PROTECTED'],
        'require' => self::$tokens['T_REQUIRE'],
        'require_once' => self::$tokens['T_REQUIRE_ONCE'],
        'return' => self::$tokens['T_RETURN'],
        'static' => self::$tokens['T_STATIC'],
        'switch' => self::$tokens['T_SWITCH'],
        'throw' => self::$tokens['T_THROW'],
        'try' => self::$tokens['T_TRY'],
        'unset' => self::$tokens['T_UNSET'],
        'use' => self::$tokens['T_USE'],
        'var' => self::$tokens['T_VAR'],
        'while' => self::$tokens['T_WHILE']
    ];

    // Type casting tokens

    self::$typeCasting = [
        'array' => self::$tokens['T_ARRAY_CAST'],
        'bool' => self::$tokens['T_BOOL_CAST'],
        'boolean' => self::$tokens['T_BOOL_CAST'],
        'real' => self::$tokens['T_DOUBLE_CAST'],
        'double' => self::$tokens['T_DOUBLE_CAST'],
        'float' => self::$tokens['T_DOUBLE_CAST'],
        'int' => self::$tokens['T_INT_CAST'],
        'integer' => self::$tokens['T_INT_CAST'],
        'object' => self::$tokens['T_OBJECT_CAST'],
        'string' => self::$tokens['T_STRING_CAST'],
        'unset' => self::$tokens['T_UNSET_CAST'],
        'binary' => self::$tokens['T_STRING_CAST']
    ];

    // Symbols tokens with 2 characters

    self::$symbols2chars = [
        '&=' => self::$tokens['T_AND_EQUAL'],
        '&&' => self::$tokens['T_BOOLEAN_AND'],
        '||' => self::$tokens['T_BOOLEAN_OR'],
        '?>' => self::$tokens['T_CLOSE_TAG'],
        '%>' => self::$tokens['T_CLOSE_TAG'],
        '.=' => self::$tokens['T_CONCAT_EQUAL'],
        '--' => self::$tokens['T_DEC'],
        '/=' => self::$tokens['T_DIV_EQUAL'],
        '=>' => self::$tokens['T_DOUBLE_ARROW'],
        '::' => self::$tokens['T_PAAMAYIM_NEKUDOTAYIM'],
        '++' => self::$tokens['T_INC'],
        '==' => self::$tokens['T_IS_EQUAL'],
        '>=' => self::$tokens['T_IS_GREATER_OR_EQUAL'],
        '!=' => self::$tokens['T_IS_NOT_EQUAL'],
        '<>' => self::$tokens['T_IS_NOT_EQUAL'],
        '<=' => self::$tokens['T_IS_SMALLER_OR_EQUAL'],
        '-=' => self::$tokens['T_MINUS_EQUAL'],
        '%=' => self::$tokens['T_MOD_EQUAL'],
        '*=' => self::$tokens['T_MUL_EQUAL'],
        '->' => self::$tokens['T_OBJECT_OPERATOR'],
        '|=' => self::$tokens['T_OR_EQUAL'],
        '+=' => self::$tokens['T_PLUS_EQUAL'],
        '<<' => self::$tokens['T_SL'],
        '>>' => self::$tokens['T_SR'],
        '^=' => self::$tokens['T_XOR_EQUAL'],
        '<?' => self::$tokens['T_OPEN_TAG']
    ];

    // Symbols tokens with 3 characters

    self::$symbols3chars = [
        '===' => self::$tokens['T_IS_IDENTICAL'],
        '!==' => self::$tokens['T_IS_NOT_IDENTICAL'],
        '<<=' => self::$tokens['T_SL_EQUAL'],
        '>>=' => self::$tokens['T_SR_EQUAL'],
        '<?=' => self::$tokens['T_OPEN_TAG_WITH_ECHO'],
        '<%=' => self::$tokens['T_OPEN_TAG_WITH_ECHO']
    ];

    // Buffer tokens

    self::$bufferTokens = [
        'html' => self::$tokens['T_INLINE_HTML'],
        'inlineComment' => self::$tokens['T_COMMENT'],
        'comment' => self::$tokens['T_COMMENT'],
        'docComment' => self::$tokens['T_DOC_COMMENT'],
        'singleQuote' => self::$tokens['T_CONSTANT_ENCAPSED_STRING'],
        'doubleQuotes' => self::$tokens['T_CONSTANT_ENCAPSED_STRING'],
        'nowdoc' => self::$tokens['T_ENCAPSED_AND_WHITESPACE'],
        'heredoc' => self::$tokens['T_ENCAPSED_AND_WHITESPACE']
    ];

}

    // Function to emit tokens
    private function emitToken($token, $code = null, $preventBuffer = null, $l = null) {

        if (!$preventBuffer && $this->bufferType) {
            $this->buffer .= $token;
            $this->lastToken = null;

        } else {
            // NB! Two-lines OR operator in JavaScript works not as expected in PHP!
            // $this->lastToken = $code || $token;
            $this->lastToken = $code ? $code : $token;
            $line = $l ? $l : $this->line;
            $this->ret[] = $code ? [$code, $token, $line] : $token;
        }
    }

    // Function to emit and close the current buffer
    private function emitBuffer() {

        if ($this->buffer)
            $this->emitToken($this->buffer, self::$bufferTokens[$this->bufferType], true, $this->lineBuffer);

        $this->buffer = "";
        $this->bufferType = null;
    }

    // Function to check if the token at the current index is escaped

    private function isEscaped($s = null) {

        $escaped = false;
        $index = $s ? $s : $this->i;
        $c = ($index) - 1;
        for (; $c >= 0; $c--) {

            if ($this->source[$c] !== "\\")
                break;

            $escaped = !$escaped;
        }

        return $escaped;
    }

    // Returns the number of line feed characters in the given string

    private function countNewLines($str) {
        return substr_count($str, "\n");
    }

    // Get the part of source that is between the current index and the index of the limit character

    private function getBufferAndEmit($start, $type, $limit, $canBeEscaped = false) {

        $startL = mb_strlen($start);
        $startPos = $this->i + $startL;
        $pos = mb_strpos($this->source, $limit, $startPos);

        $this->lineBuffer = $this->line;
        if ($canBeEscaped)
            while ($pos !== -1 && $this->isEscaped($pos))
                $pos = mb_strpos($this->source, $limit, $pos + 1);

        $this->bufferType = $type;
        if ($pos == -1)
            $this->buffer = $start . mb_substr($this->source, $startPos);
        else
            $this->buffer = $start . mb_substr($this->source, $startPos, $pos - $startPos) . $limit;


        $this->line += $this->countNewLines($this->buffer);
        $this->emitBuffer();

        // If limit is not found, set i to the position of the end of the buffered characters
        if ($pos == -1)
            $this->i = $this->i + mb_strlen($this->buffer);
        else
            $this->i = $pos + mb_strlen($limit) - 1;

    }

    // This function is used to split a double quoted string or a heredoc buffer after a variable
    // has been found inside it

    private function splitString() {

        // Don't emit empty buffers
        if (!$this->buffer) return;

        // If the buffer is a double quoted string and it has not yet been splitted, emit the double
        // quotes as a token without an associated code

        if ($this->bufferType == "doubleQuotes" && !$this->split) {
            $this->split = true;
            $this->emitToken('"', null, true);
            $this->buffer = mb_substr($this->buffer, 1);
        }

        if ($this->buffer)
            $this->emitToken($this->buffer, self::$tokens['T_ENCAPSED_AND_WHITESPACE'], true, $this->lineBuffer);
        $this->buffer = "";
        $lineBuffer = $this->line;
    }

    // Checks if the given ascii identifies a whitespace
    private function isWhitespace($ascii) {
        return in_array($ascii, [9, 10, 13, 32]);
    }

    // Get next whitespaces
    private function getWhitespaces() {
        $ret = "";
        for ($this->i++; $this->i < $this->length; $this->i++) {
            $char = $this->source[$this->i];
            $ascii = ord($char);

            if ($this->isWhitespace($ascii)) {
                $ret .= $char;
            } else {
                $this->i--;
                break;
            }
        }

        return $ret;
    }

    // Get next word
    private function getWord($i) {
        preg_match("/^[a-zA-Z_]\w*/", mb_substr($this->source, $i), $match);
        return $match ? $match[0] : null;
    }

    // Get next heredoc declaration
    private function getHeredocWord() {
        preg_match("/^<<< *([\'\"]?[a-zA-Z]\w*)[\'\"]?\r?\n/", mb_substr($this->source, $this->i), $match);
        return $match ? $match : null;
    }

    // Get next type casting declaration
    private function getTypeCasting() {
        preg_match("/^\( *([a-zA-Z]+) *\)/", mb_substr($this->source, $this->i), $match);
        if ($match && $match[1] && array_key_exist(strtolower($match[1]), $this->typeCasting))
            return $match;
        else
            return null;
    }

    // Get next php long open declaration
    private function getLongOpenDeclaration($i) {
        preg_match("/^php(?:\r?\s)?/i", mb_substr($this->source, $i), $match);
        return $match ? $match : null;
    }

    // Get next integer or float number
    private function getNumber() {

        $rnum = "/^(?:((?:\d+(?:\.\d*)?|\d*\.\d+)[eE][\+\-]?\d+|\d*\.\d+|\d+\.\d*)|(\d+(?:x[0-9a-fA-F]+)?))/";
        preg_match($rnum, mb_substr($this->source, $this->i), $match);

        if (!$match) return null;

        if ($match[2]) {
            $isHex = mb_strpos(strtolower($match[2]), "x") !== false;

            // FIXME! PHP64 can use really long numbers
            // If it's greater than 2147483648 it's considered as a floating point number

            $val = $isHex ? hexdec($match[2]) : intval($match[2]);
            if ($val < 2147483648)
                return [ $match[2], self::$tokens['T_LNUMBER'] ];

            return [ $match[2], self::$tokens['T_DNUMBER'] ];
        }
        return [ $match[1], self::$tokens['T_DNUMBER'] ];
    }

}
