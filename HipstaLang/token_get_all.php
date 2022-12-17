<?php

print_r(get_defined_constants(true)['tokenizer']);
die();

    require('PHPTokenizer.php');

    // PHP Tokens List : https://github.com/nikic/PHP-Parser/blob/master/lib/PhpParser/Parser/Tokens.php



$code = <<<'CODE'

<?php

	// first comment

	class Server {

        private $init;
        public function __construct($init) {
            $this->init = $init;
        }

        public function show($arr) {
            foreach($arr as $key => $value)
                echo "\n ${key} and ${value} \n";
        }
	}

	$server = new Server(2018);
    $server->show(["first" => 1, "second" => "two"]);

	function hello($message) {
		echo "Hello " . $message;
	}

	hello("Hipsta!");

	die();

CODE;

echo "\n-----------------------------------------------------------------------\n";

    $res = (new PHPTokenizer())->token_get_all($code);
    //$res = Tokenizer::token_get_all($code);
    //var_dump($res);

echo "\n-----------------------------------------------------------------------\n";

    $mytokens = (new PHPTokenizer())->token_get_all($code);
    foreach ($mytokens as $token)
        if (is_array($token))
            echo "Line {$token[2]}: ", PHPTokenizer::token_name($token[0]), " ('{$token[1]}')", PHP_EOL;

echo "\n-----------------------------------------------------------------------\n";

    var_dump(token_get_all($code));

echo "\n-----------------------------------------------------------------------\n";

    $tokens = token_get_all($code);
    foreach ($tokens as $token)
        if (is_array($token))
            echo "Line {$token[2]}: ", token_name($token[0]), " ('{$token[1]}')", PHP_EOL;

echo "\n-----------------------------------------------------------------------";

    for ($i = 0; $i < count($mytokens); $i++) {
        if (is_array($mytokens[$i]))
            echo "Line {$mytokens[$i][2]}: " . PHPTokenizer::token_name($mytokens[$i][0]) . " ('{$mytokens[$i][1]}')" .
            " == " . token_name($tokens[$i][0]) . " ('{$tokens[$i][1]}')" . PHP_EOL;
        else
            echo $mytokens[$i] . " == " . $tokens[$i] . PHP_EOL;

    }
