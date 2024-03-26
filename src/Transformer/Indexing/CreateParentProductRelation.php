<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDKPipelines\Transformer\Indexing;

use Klevu\PhpSDKPipelines\Provider\Argument\Transformer\Indexing\CreateParentProductRelationArgumentProvider;
use Klevu\Pipelines\Exception\ObjectManager\InvalidClassException;
use Klevu\Pipelines\Model\ArgumentIterator;
use Klevu\Pipelines\ObjectManager\Container;
use Klevu\Pipelines\Transformer\TransformerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CreateParentProductRelation implements TransformerInterface
{
    final public const ARGUMENT_INDEX_PARENT_ID = CreateParentProductRelationArgumentProvider::ARGUMENT_INDEX_PARENT_ID;

    /**
     * @var CreateParentProductRelationArgumentProvider
     */
    private readonly CreateParentProductRelationArgumentProvider $argumentProvider;

    /**
     * @param CreateParentProductRelationArgumentProvider|null $argumentProvider
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        ?CreateParentProductRelationArgumentProvider $argumentProvider = null,
    ) {
        $container = Container::getInstance();

        $argumentProvider ??= $container->get(CreateParentProductRelationArgumentProvider::class);
        try {
            $this->argumentProvider = $argumentProvider; // @phpstan-ignore-line (we catch TypeError)
        } catch (\TypeError) {
            throw new InvalidClassException(
                identifier: CreateParentProductRelationArgumentProvider::class,
                instance: $argumentProvider,
            );
        }
    }

    /**
     * @param mixed $data
     * @param ArgumentIterator|null $arguments
     * @param \ArrayAccess<string|int, mixed>|null $context
     * @return mixed[]
     */
    public function transform(
        mixed $data,
        ?ArgumentIterator $arguments = null,
        ?\ArrayAccess $context = null,
    ): array {
        $parentIdArgumentValue = $this->argumentProvider->getParentIdArgumentValue(
            arguments: $arguments,
            extractionPayload: $data,
            extractionContext: $context,
        );
        $recordTypeArgumentValue = $this->argumentProvider->getRecordTypeArgumentValue(
            arguments: $arguments,
            extractionPayload: $data,
            extractionContext: $context,
        );

        return [
            'type' => $recordTypeArgumentValue,
            'values' => [
                $parentIdArgumentValue,
            ],
        ];
    }
}
