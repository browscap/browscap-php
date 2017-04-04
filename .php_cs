<?php
declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->files()
    ->name('*.php')
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
;

ini_set('memory_limit', '-1');

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
    ])
    ->setFinder($finder);
