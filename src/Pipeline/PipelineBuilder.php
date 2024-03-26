<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDKPipelines\Pipeline;

use Klevu\PhpSDKPipelines\ObjectManager\TransformerManager;
use Klevu\PhpSDKPipelines\ObjectManager\ValidatorManager;
use Klevu\Pipelines\ObjectManager\Container;
use Klevu\Pipelines\ObjectManager\ObjectManagerInterface;
use Klevu\Pipelines\Pipeline\ConfigurationBuilder;
use Klevu\Pipelines\Pipeline\PipelineBuilder as BasePipelineBuilder;
use Klevu\Pipelines\Pipeline\PipelineFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

class PipelineBuilder extends BasePipelineBuilder
{
    /**
     * @param ConfigurationBuilder|null $configurationBuilder
     * @param ObjectManagerInterface|null $transformerManager
     * @param ObjectManagerInterface|null $validatorManager
     * @param PipelineFactoryInterface|null $pipelineFactory
     * @param string|null $defaultPipeline
     * @param LoggerInterface|null $logger
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        ?ConfigurationBuilder $configurationBuilder = null,
        ?ObjectManagerInterface $transformerManager = null,
        ?ObjectManagerInterface $validatorManager = null,
        ?PipelineFactoryInterface $pipelineFactory = null,
        ?LoggerInterface $logger = null,
        ?string $defaultPipeline = null,
    ) {
        $container = Container::getInstance();
        if ($container instanceof ObjectManagerInterface) {
            $container->addSharedInstance(
                identifier: BasePipelineBuilder::class,
                instance: $this,
            );
        }

        if (null === $transformerManager) {
            /** @var ObjectManagerInterface $transformerManager */
            $transformerManager = $container->get(TransformerManager::class);
        }
        if (null === $validatorManager) {
            /** @var ObjectManagerInterface $validatorManager */
            $validatorManager = $container->get(ValidatorManager::class);
        }
        if (null === $pipelineFactory) {
            /** @var PipelineFactory $pipelineFactory */
            $pipelineFactory = $container->get(PipelineFactory::class);
        }

        parent::__construct(
            configurationBuilder: $configurationBuilder,
            transformerManager: $transformerManager,
            validatorManager: $validatorManager,
            pipelineFactory: $pipelineFactory,
            logger: $logger,
            defaultPipeline: $defaultPipeline,
        );
    }
}
