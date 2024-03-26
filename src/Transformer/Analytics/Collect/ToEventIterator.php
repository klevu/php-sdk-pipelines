<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDKPipelines\Transformer\Analytics\Collect;

use Klevu\PhpSDK\Model\Analytics\Collect\Event;
use Klevu\PhpSDK\Model\Analytics\Collect\EventIterator;
use Klevu\Pipelines\Exception\Transformation\InvalidInputDataException;
use Klevu\Pipelines\Exception\TransformationException;
use Klevu\Pipelines\Model\ArgumentIterator;
use Klevu\Pipelines\Transformer\TransformerInterface;

class ToEventIterator implements TransformerInterface
{
    /**
     * @param mixed $data
     * @param ArgumentIterator|null $arguments
     * @param \ArrayAccess<string|int, mixed>|null $context
     * @return EventIterator
     * @throws TransformationException
     */
    public function transform(
        mixed $data,
        // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        ?ArgumentIterator $arguments = null,
        // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
        ?\ArrayAccess $context = null,
    ): EventIterator {
        switch (true) {
            case $data instanceof EventIterator:
                $return = $data;
                break;

            case $data instanceof Event:
                $return = new EventIterator([$data]);
                break;

            case is_array($data):
                try {
                    $return = new EventIterator($data);
                } catch (\ValueError $exception) {
                    throw new TransformationException(
                        transformerName: $this::class,
                        errors: [
                            $exception->getMessage(),
                        ],
                        arguments: $arguments,
                        data: $data,
                        previous: $exception,
                    );
                }
                break;

            default:
                throw new InvalidInputDataException(
                    transformerName: $this::class,
                    expectedType: sprintf(
                        '%s|%s[]|%s',
                        Event::class,
                        Event::class,
                        EventIterator::class,
                    ),
                    arguments: $arguments,
                    data: $data,
                );
        }

        return $return;
    }
}
