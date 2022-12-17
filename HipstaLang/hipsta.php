<?php

require __DIR__ . '/vendor/autoload.php';

//echo __DIR__ . '/../vendor/autoload.php'; die();

// ----------------------------------------------------------------------------

$code = <<<CODE

<h1>Good old HTML here :)</h1>

<?php

function hello() {
    echo "Hello Hipsta!";
}

hello();

CODE;

// ----------------------------------------------------------------------------

// foreach ([__DIR__ . '/../../../autoload.php', __DIR__ . '/../vendor/autoload.php'] as $file) {
//foreach ([__DIR__ . '/../../../autoload.php', __DIR__ . '/vendor/autoload.php'] as $file) {
//    if (file_exists($file)) {
  //      require $file;
    //    break;
//    }
//}

// ----------------------------------------------------------------------------

use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;

$parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
//$parser = (new ParserFactory)->create(ParserFactory::HIPSTA);

try {
    $ast = $parser->parse($code);
} catch (Error $error) {
    echo "Parse error: {$error->getMessage()}\n";
    return;
}

$dumper = new NodeDumper;
// echo $dumper->dump($ast) . "\n";

$prettyPrinter = new PrettyPrinter\Standard;
echo $prettyPrinter->prettyPrintFile($ast);

die();

eval($code);

die();

?>

# Your first words in Hipsta

def hello
    echo "Hello Hipsta!"

hello()
    