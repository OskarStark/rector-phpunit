<?php

declare(strict_types=1);

namespace Rector\PHPUnit\NodeFinder;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;

final class DataProviderClassMethodFinder
{
    public function __construct(
        private readonly PhpDocInfoFactory $phpDocInfoFactory
    ) {
    }

    /**
     * @return ClassMethod[]
     */
    public function find(Class_ $class): array
    {
        $dataProviderMethodNames = $this->resolverDataProviderClassMethodNames($class);

        $dataProviderClassMethods = [];
        foreach ($dataProviderMethodNames as $dataProviderMethodName) {
            $dataProviderClassMethod = $class->getMethod($dataProviderMethodName);
            if (! $dataProviderClassMethod instanceof ClassMethod) {
                continue;
            }

            $dataProviderClassMethods[] = $dataProviderClassMethod;
        }

        return $dataProviderClassMethods;
    }

    /**
     * @return string[]
     */
    private function resolverDataProviderClassMethodNames(Class_ $class): array
    {
        $dataProviderMethodNames = [];

        foreach ($class->getMethods() as $classMethod) {
            $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($classMethod);

            $dataProviderTagValueNodes = $phpDocInfo->getTagsByName('dataProvider');
            if ($dataProviderTagValueNodes === []) {
                continue;
            }

            foreach ($dataProviderTagValueNodes as $dataProviderTagValueNode) {
                if (! $dataProviderTagValueNode->value instanceof GenericTagValueNode) {
                    continue;
                }

                $dataProviderMethodNames[] = $this->resolveMethodName($dataProviderTagValueNode->value);
            }
        }

        return $dataProviderMethodNames;
    }

    private function resolveMethodName(GenericTagValueNode $genericTagValueNode): string
    {
        $rawValue = $genericTagValueNode->value;
        return trim($rawValue, '()');
    }
}
