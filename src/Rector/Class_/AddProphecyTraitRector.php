<?php

declare(strict_types=1);

namespace Rector\PHPUnit\Rector\Class_;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use Rector\Core\NodeManipulator\ClassInsertManipulator;
use Rector\Core\NodeManipulator\ClassManipulator;
use Rector\Core\Rector\AbstractRector;
use Rector\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @changelog https://github.com/sebastianbergmann/phpunit/issues/4142
 * @changelog https://github.com/sebastianbergmann/phpunit/issues/4141
 * @changelog https://github.com/sebastianbergmann/phpunit/issues/4149
 *
 * @see \Rector\PHPUnit\Tests\Rector\Class_\AddProphecyTraitRector\AddProphecyTraitRectorTest
 */
final class AddProphecyTraitRector extends AbstractRector
{
    /**
     * @var string
     */
    private const PROPHECY_TRAIT = 'Prophecy\PhpUnit\ProphecyTrait';

    public function __construct(
        private readonly ClassInsertManipulator $classInsertManipulator,
        private readonly ClassManipulator $classManipulator,
        private readonly TestsNodeAnalyzer $testsNodeAnalyzer
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add Prophecy trait for method using $this->prophesize()',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;

final class ExampleTest extends TestCase
{
    public function testOne(): void
    {
        $prophecy = $this->prophesize(\AnInterface::class);
    }
}
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

final class ExampleTest extends TestCase
{
    use ProphecyTrait;

    public function testOne(): void
    {
        $prophecy = $this->prophesize(\AnInterface::class);
    }
}
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($this->shouldSkipClass($node)) {
            return null;
        }

        $this->classInsertManipulator->addAsFirstTrait($node, self::PROPHECY_TRAIT);

        return $node;
    }

    private function shouldSkipClass(Class_ $class): bool
    {
        $hasProphesizeMethodCall = (bool) $this->betterNodeFinder->findFirst(
            $class,
            fn (Node $node): bool => $this->testsNodeAnalyzer->isAssertMethodCallName($node, 'prophesize')
        );

        if (! $hasProphesizeMethodCall) {
            return true;
        }

        return $this->classManipulator->hasTrait($class, self::PROPHECY_TRAIT);
    }
}
