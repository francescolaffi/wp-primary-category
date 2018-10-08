<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__.'/src/',
    ])
    ->append([
        __DIR__.'/primary-category.php',
        __FILE__,
    ])
;

return PhpCsFixer\Config::create()
    ->setFinder($finder)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP71Migration' => true,
        '@PHP71Migration:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'class_attributes_separation' => ['elements' => ['method', 'property']],
        'native_function_invocation' => true,
        'ordered_imports' => true,
    ])
    ->setRiskyAllowed(true)
;
