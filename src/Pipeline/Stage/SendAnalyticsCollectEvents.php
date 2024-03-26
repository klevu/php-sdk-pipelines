<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDKPipelines\Pipeline\Stage;

use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Analytics\Collect\EventIterator;
use Klevu\PhpSDK\Service\Analytics\CollectService as AnalyticsCollectService;
use Klevu\PhpSDKPipelines\Model\ApiPipelineResult;
use Klevu\PhpSDKPipelines\Traits\InjectUserAgentProvidersTrait;
use Klevu\Pipelines\Exception\ObjectManager\InvalidClassException;
use Klevu\Pipelines\Exception\Pipeline\InvalidPipelinePayloadException;
use Klevu\Pipelines\ObjectManager\Container;
use Klevu\Pipelines\Pipeline\PipelineInterface;
use Klevu\Pipelines\Pipeline\StagesNotSupportedTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SendAnalyticsCollectEvents implements PipelineInterface
{
    use StagesNotSupportedTrait;
    use InjectUserAgentProvidersTrait;

    /**
     * @var AnalyticsCollectService
     */
    private AnalyticsCollectService $analyticsCollectService;
    /**
     * @var string
     */
    private readonly string $identifier;

    /**
     * @param AnalyticsCollectService|null $analyticsCollectService
     * @param PipelineInterface[] $stages
     * @param mixed[]|null $args
     * @param string $identifier
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        ?AnalyticsCollectService $analyticsCollectService = null,
        array $stages = [],
        ?array $args = null,
        string $identifier = '',
    ) {
        $container = Container::getInstance();

        $analyticsCollectService ??= $container->get(AnalyticsCollectService::class);
        try {
            // @phpstan-ignore-next-line Covered by TypeError catch
            $this->analyticsCollectService = $analyticsCollectService;
        } catch (\TypeError) {
            throw new InvalidClassException(
                identifier: AnalyticsCollectService::class,
                instance: $analyticsCollectService,
            );
        }
        
        $this->injectUserAgentProviders(
            // @phpstan-ignore-next-line Obviously not mixed given it's the class property
            userAgentProvider: $this->analyticsCollectService->getUserAgentProvider(),
        );

        array_walk($stages, [$this, 'addStage']);
        if ($args) {
            $this->setArgs($args);
        }

        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param mixed[] $args
     * @return void
     */
    public function setArgs(
        array $args, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    ): void {
        // Arguments not implemented
    }

    /**
     * @param mixed $payload
     * @param \ArrayAccess<string|int, mixed>|null $context
     * @return ApiPipelineResult
     * @throws InvalidPipelinePayloadException
     */
    public function execute(
        mixed $payload,
        ?\ArrayAccess $context = null, // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    ): ApiPipelineResult {
        if (!($payload instanceof EventIterator)) {
            throw new InvalidPipelinePayloadException(
                pipelineName: $this::class,
                message: sprintf(
                    'Stage: SendAnalyticsCollectEvents expects payload of type %s; Received: %s',
                    EventIterator::class,
                    get_debug_type($payload),
                ),
            );
        }

        $messages = [];
        try {
            $apiResponse = $this->analyticsCollectService->send($payload);
            $success = $apiResponse->isSuccess();
            $messages = $apiResponse->getMessages();
        } catch (BadRequestException | BadResponseException | ValidationException $exception) {
            $success = false;
            $messages = array_merge(
                $messages,
                [$exception->getMessage()],
                $exception->getErrors(),
            );
        }

        return new ApiPipelineResult(
            success: $success,
            payload: $payload,
            messages: $messages,
            apiResponse: $apiResponse ?? null,
        );
    }
}
