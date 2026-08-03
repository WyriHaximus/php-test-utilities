<?php

declare(strict_types=1);

namespace WyriHaximus\TestUtilities\Rector\Rules;

use PhpParser\Node;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Stmt\ClassMethod;
use PHPUnit\Framework\Attributes\Test;
use Rector\Php80\NodeAnalyzer\PhpAttributeAnalyzer;
use Rector\PhpAttribute\NodeFactory\PhpAttributeGroupFactory;
use Rector\PHPUnit\Enum\PHPUnitAttribute;
use Rector\PHPUnit\NodeAnalyzer\TestsNodeAnalyzer;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

use function array_merge;
use function lcfirst;
use function str_starts_with;
use function substr;

final class ReplaceTestFunctionPrefixWithAttributeRector extends AbstractRector
{
    public function __construct(
        private readonly TestsNodeAnalyzer $testsNodeAnalyzer,
        private readonly PhpAttributeGroupFactory $phpAttributeGroupFactory,
        private readonly PhpAttributeAnalyzer $phpAttributeAnalyzer,
    ) {
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace @test with prefixed function', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class SomeTest extends \PHPUnit\Framework\TestCase
{
    public function testOnePlusOneShouldBeTwo()
    {
        $this->assertSame(2, 1+1);
    }
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
class SomeTest extends \PHPUnit\Framework\TestCase
{
    #[Test]
    public function onePlusOneShouldBeTwo()
    {
        $this->assertSame(2, 1+1);
    }
}
CODE_SAMPLE,
            ),
        ]);
    }

    /** @return array<class-string<Node>> */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /** @param ClassMethod $node */
    public function refactor(Node $node): Node|null
    {
        if (! $this->testsNodeAnalyzer->isInTestClass($node)) {
            return null;
        }

        if (! str_starts_with($node->name->toString(), 'test')) {
            return null;
        }

        if ($this->phpAttributeAnalyzer->hasPhpAttributes($node, [PHPUnitAttribute::TEST])) {
            return null;
        }

        if ($node->name->toString() !== 'test' && $node->name->toString() !== 'test_') {
            if (str_starts_with($node->name->toString(), 'test_')) {
                $node->name->name = lcfirst(substr($node->name->name, 5));
            } elseif (str_starts_with($node->name->toString(), 'test')) {
                $node->name->name = lcfirst(substr($node->name->name, 4));
            }
        }

        $coversAttributeGroup = $this->createAttributeGroup();
        $node->attrGroups     = array_merge($node->attrGroups, [$coversAttributeGroup]);

        return $node;
    }

    private function createAttributeGroup(): AttributeGroup
    {
        $attributeClass = Test::class;

        return $this->phpAttributeGroupFactory->createFromClassWithItems($attributeClass, []);
    }
}
