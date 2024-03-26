<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDKPipelines\Transformer\Indexing;

use Klevu\PhpSDK\Api\Model\Indexing\RecordInterface;
use Klevu\PhpSDK\Model\Indexing\RecordFactory;
use Klevu\Pipelines\Exception\ObjectManager\InvalidClassException;
use Klevu\Pipelines\Exception\Transformation\InvalidInputDataException;
use Klevu\Pipelines\Exception\TransformationException;
use Klevu\Pipelines\Model\ArgumentIterator;
use Klevu\Pipelines\ObjectManager\Container;
use Klevu\Pipelines\Transformer\TransformerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ToRecord implements TransformerInterface
{
    /**
     * @var RecordFactory
     */
    private readonly RecordFactory $recordFactory;

    /**
     * @param RecordFactory|null $recordFactory
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        ?RecordFactory $recordFactory = null,
    ) {
        $container = Container::getInstance();

        $recordFactory ??= $container->get(RecordFactory::class);
        try {
            $this->recordFactory = $recordFactory; // @phpstan-ignore-line (we catch TypeError)
        } catch (\TypeError) {
            throw new InvalidClassException(
                identifier: RecordFactory::class,
                instance: $recordFactory,
            );
        }
    }

    /**
     * @param mixed $data
     * @param ArgumentIterator|null $arguments
     * @param \ArrayAccess<string|int, mixed>|null $context
     * @return RecordInterface
     * @throws TransformationException
     */
    public function transform(
        mixed $data,
        // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        ?ArgumentIterator $arguments = null,
        // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        ?\ArrayAccess $context = null,
    ): RecordInterface {
        if (!is_array($data)) {
            throw new InvalidInputDataException(
                transformerName: $this::class,
                expectedType: 'array',
                arguments: $arguments,
                data: $data,
            );
        }

        return $this->recordFactory->create($data);
    }
}
