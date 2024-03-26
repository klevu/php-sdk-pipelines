<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDKPipelines\Pipeline;

use Klevu\PhpSDKPipelines\ObjectManager\PipelineFqcnProvider;
use Klevu\Pipelines\Exception\ObjectManager\InvalidClassException;
use Klevu\Pipelines\ObjectManager\Container;
use Klevu\Pipelines\ObjectManager\PipelineFqcnProviderInterface;
use Klevu\Pipelines\Pipeline\PipelineFactory as BasePipelineFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class PipelineFactory extends BasePipelineFactory
{
    /**
     * @param PipelineFqcnProviderInterface|null $pipelineFqcnProvider
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(?PipelineFqcnProviderInterface $pipelineFqcnProvider = null)
    {
        $container = Container::getInstance();

        if (null === $pipelineFqcnProvider) {
            $pipelineFqcnProvider = $container->get(PipelineFqcnProvider::class);
            if (!($pipelineFqcnProvider instanceof PipelineFqcnProviderInterface)) {
                throw new InvalidClassException(
                    identifier: PipelineFqcnProvider::class,
                    instance: $pipelineFqcnProvider, // @phpstan-ignore-line Variable is clearly an object at this stage
                );
            }
        }

        parent::__construct($pipelineFqcnProvider);
    }
}
