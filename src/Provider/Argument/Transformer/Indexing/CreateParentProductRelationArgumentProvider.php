<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDKPipelines\Provider\Argument\Transformer\Indexing;

use Klevu\PhpSDKPipelines\Transformer\Indexing\CreateParentProductRelation as CreateParentProductRelationTransformer;
use Klevu\Pipelines\Exception\ObjectManager\InvalidClassException;
use Klevu\Pipelines\Exception\Transformation\InvalidTransformationArgumentsException;
use Klevu\Pipelines\Model\ArgumentIterator;
use Klevu\Pipelines\ObjectManager\Container;
use Klevu\Pipelines\Provider\ArgumentProvider;
use Klevu\Pipelines\Provider\ArgumentProviderInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CreateParentProductRelationArgumentProvider
{
    final public const ARGUMENT_INDEX_PARENT_ID = 0;
    final public const ARGUMENT_INDEX_RECORD_TYPE = 1;

    /**
     * @var ArgumentProviderInterface
     */
    private readonly ArgumentProviderInterface $argumentProvider;
    /**
     * @var string
     */
    private readonly string $defaultRecordType;

    /**
     * @param ArgumentProviderInterface|null $argumentProvider
     * @param string $defaultRecordType
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        ?ArgumentProviderInterface $argumentProvider = null,
        string $defaultRecordType = 'KLEVU_PRODUCT',
    ) {
        $container = Container::getInstance();

        $argumentProvider ??= $container->get(ArgumentProvider::class);
        try {
            $this->argumentProvider = $argumentProvider; // @phpstan-ignore-line Invalid type covered by TypeError catch
        } catch (\TypeError) {
            throw new InvalidClassException(
                identifier: ArgumentProvider::class,
                instance: $argumentProvider,
            );
        }

        $this->defaultRecordType = $defaultRecordType;
    }

    /**
     * @param ArgumentIterator|null $arguments
     * @param mixed|null $extractionPayload
     * @param \ArrayAccess<string|int, mixed>|null $extractionContext
     * @return string
     */
    public function getParentIdArgumentValue(
        ?ArgumentIterator $arguments,
        mixed $extractionPayload = null,
        ?\ArrayAccess $extractionContext = null,
    ): string {
        $argumentValue = $this->argumentProvider->getArgumentValueWithExtractionExpansion(
            arguments: $arguments,
            argumentKey: self::ARGUMENT_INDEX_PARENT_ID,
            defaultValue: null,
            extractionPayload: $extractionPayload,
            extractionContext: $extractionContext,
        );

        switch (true) {
            case empty($argumentValue):
                throw new InvalidTransformationArgumentsException(
                    transformerName: CreateParentProductRelationTransformer::class,
                    errors: [
                        sprintf(
                            'Parent ID argument (%s) value is required',
                            self::ARGUMENT_INDEX_PARENT_ID,
                        ),
                    ],
                    arguments: $arguments,
                    data: $extractionPayload,
                );

            case !is_scalar($argumentValue):
                throw new InvalidTransformationArgumentsException(
                    transformerName: CreateParentProductRelationTransformer::class,
                    errors: [
                        sprintf(
                            'Invalid Parent ID argument (%s)',
                            self::ARGUMENT_INDEX_PARENT_ID,
                        ),
                    ],
                    arguments: $arguments,
                    data: $extractionPayload,
                );
        }

        /** @var scalar $argumentValue */
        return (string)$argumentValue;
    }

    /**
     * @param ArgumentIterator|null $arguments
     * @param mixed|null $extractionPayload
     * @param \ArrayAccess<string|int, mixed>|null $extractionContext
     * @return string
     */
    public function getRecordTypeArgumentValue(
        ?ArgumentIterator $arguments,
        mixed $extractionPayload = null,
        ?\ArrayAccess $extractionContext = null,
    ): string {
        $argumentValue = $this->argumentProvider->getArgumentValueWithExtractionExpansion(
            arguments: $arguments,
            argumentKey: self::ARGUMENT_INDEX_RECORD_TYPE,
            defaultValue: $this->defaultRecordType,
            extractionPayload: $extractionPayload,
            extractionContext: $extractionContext,
        );

        switch (true) {
            case empty($argumentValue):
                throw new InvalidTransformationArgumentsException(
                    transformerName: CreateParentProductRelationTransformer::class,
                    errors: [
                        sprintf(
                            'Record Type argument (%s) value is required',
                            self::ARGUMENT_INDEX_RECORD_TYPE,
                        ),
                    ],
                    arguments: $arguments,
                    data: $extractionPayload,
                );

            case !is_string($argumentValue):
                throw new InvalidTransformationArgumentsException(
                    transformerName: CreateParentProductRelationTransformer::class,
                    errors: [
                        sprintf(
                            'Invalid Record Type argument (%s)',
                            self::ARGUMENT_INDEX_RECORD_TYPE,
                        ),
                    ],
                    arguments: $arguments,
                    data: $extractionPayload,
                );
        }

        /** @var non-empty-string $argumentValue */
        return $argumentValue;
    }
}
