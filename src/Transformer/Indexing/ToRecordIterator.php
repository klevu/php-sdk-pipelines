<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDKPipelines\Transformer\Indexing;

use Klevu\PhpSDK\Api\Model\Indexing\RecordInterface;
use Klevu\PhpSDK\Model\Indexing\RecordFactory;
use Klevu\PhpSDK\Model\Indexing\RecordIterator;
use Klevu\Pipelines\Exception\ObjectManager\InvalidClassException;
use Klevu\Pipelines\Exception\Transformation\InvalidInputDataException;
use Klevu\Pipelines\Exception\TransformationException;
use Klevu\Pipelines\Model\ArgumentIterator;
use Klevu\Pipelines\ObjectManager\Container;
use Klevu\Pipelines\Transformer\TransformerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ToRecordIterator implements TransformerInterface
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
     * @return RecordIterator
     * @throws TransformationException
     */
    public function transform(
        mixed $data,
        // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        ?ArgumentIterator $arguments = null,
        // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        ?\ArrayAccess $context = null,
    ): RecordIterator {
        switch (true) {
            case $data instanceof RecordIterator:
                $return = clone $data;
                break;

            case $data instanceof RecordInterface:
                $return = new RecordIterator([$data]);
                break;

            case is_array($data):
                $return = new RecordIterator();
                foreach ($data as $recordIndex => $record) {
                    switch (true) {
                        case $record instanceof RecordInterface:
                            break;

                        case is_array($record):
                            $record = $this->recordFactory->create($record);
                            break;

                        default:
                            throw new InvalidInputDataException(
                                transformerName: $this::class,
                                expectedType: sprintf('%s|array', RecordInterface::class),
                                errors: [
                                    sprintf(
                                        'Item #%s expected to be %s|array, received %s',
                                        $recordIndex,
                                        RecordInterface::class,
                                        get_debug_type($record),
                                    ),
                                ],
                                arguments: $arguments,
                                data: $data,
                            );
                    }

                    $return->addItem($record);
                }
                break;

            default:
                throw new InvalidInputDataException(
                    transformerName: $this::class,
                    expectedType: sprintf(
                        '%s|%s[]|%s',
                        RecordInterface::class,
                        RecordInterface::class,
                        RecordIterator::class,
                    ),
                    arguments: $arguments,
                    data: $data,
                );
        }

        return $return;
    }
}
