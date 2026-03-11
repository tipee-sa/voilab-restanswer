<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Basic\SingleLineEmptyBodyFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\FunctionNotation\NativeFunctionInvocationFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withCache(__DIR__ . '/var/cache/ecs')
    ->withParallel()
    ->withPaths([
        __DIR__ . '/lib',
        __DIR__ . '/tests',
        __DIR__ . '/ecs.php',
    ])
    ->withPreparedSets(common: true, cleanCode: true)
    ->withPhpCsFixerSets(symfony: true, symfonyRisky: true)
    ->withPreparedSets(psr12: true)
    ->withSkip([
        // These don't work well with our code style
        PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer::class => null,
        PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer::class => null,
        PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer::class => null,
        PhpCsFixer\Fixer\Phpdoc\PhpdocSummaryFixer::class => null,
        PhpCsFixer\Fixer\Phpdoc\PhpdocToCommentFixer::class => null,
        PhpCsFixer\Fixer\Phpdoc\PhpdocSeparationFixer::class => null,
        PhpCsFixer\Fixer\Phpdoc\PhpdocAnnotationWithoutDotFixer::class => null,
        PhpCsFixer\Fixer\FunctionNotation\SingleLineThrowFixer::class => null,
        PhpCsFixer\Fixer\Whitespace\BlankLineBetweenImportGroupsFixer::class => null,
        PhpCsFixer\Fixer\ClassNotation\OrderedTypesFixer::class => null,
        Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer::class => null,
        Symplify\CodingStandard\Fixer\ArrayNotation\ArrayListItemNewlineFixer::class => null,
        Symplify\CodingStandard\Fixer\ArrayNotation\StandaloneLineInMultilineArrayFixer::class => null,
        Symplify\CodingStandard\Fixer\Spacing\StandaloneLinePromotedPropertyFixer::class => null,
        PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer::class => null,
        PhpCsFixer\Fixer\Operator\NoSpaceAroundDoubleColonFixer::class => null,
        PhpCsFixer\Fixer\PhpUnit\PhpUnitStrictFixer::class => null,
    ])
    ->withRules([
        NoUnusedImportsFixer::class,
        SingleLineEmptyBodyFixer::class,
        DeclareStrictTypesFixer::class,
    ])
    ->withConfiguredRule(YodaStyleFixer::class, [
        'equal' => true,
        'identical' => true,
        'less_and_greater' => null,
    ])
    ->withConfiguredRule(TrailingCommaInMultilineFixer::class, [
        'elements' => ['arrays', 'arguments', 'parameters'],
    ])
    ->withConfiguredRule(GlobalNamespaceImportFixer::class, [
        'import_classes' => true,
        'import_constants' => true,
        'import_functions' => true,
    ])
    ->withConfiguredRule(NoSuperfluousPhpdocTagsFixer::class, [
        'allow_mixed' => true,
    ])
    ->withConfiguredRule(NativeFunctionInvocationFixer::class, [
        'scope' => 'namespaced',
    ])
    ->withConfiguredRule(ConcatSpaceFixer::class, [
        'spacing' => 'one',
    ]);
