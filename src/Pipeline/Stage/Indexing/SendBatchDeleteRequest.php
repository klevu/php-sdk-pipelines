<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDKPipelines\Pipeline\Stage\Indexing;

use Klevu\PhpSDK\Api\Model\Indexing\RecordInterface;
use Klevu\PhpSDK\Api\Service\Indexing\BatchDeleteServiceInterface;
use Klevu\PhpSDK\Exception\Api\BadRequestException;
use Klevu\PhpSDK\Exception\Api\BadResponseException;
use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\AccountCredentials;
use Klevu\PhpSDK\Model\Indexing\RecordIterator;
use Klevu\PhpSDK\Service\Indexing\Batch\DeleteService as BatchDeleteService;
use Klevu\PhpSDK\Validator\JsApiKeyValidator;
use Klevu\PhpSDK\Validator\RestAuthKeyValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;
use Klevu\PhpSDKPipelines\Model\ApiPipelineResult;
use Klevu\PhpSDKPipelines\Traits\InjectUserAgentProvidersTrait;
use Klevu\Pipelines\Exception\ExtractionExceptionInterface;
use Klevu\Pipelines\Exception\ObjectManager\InvalidClassException;
use Klevu\Pipelines\Exception\Pipeline\InvalidPipelineArgumentsException;
use Klevu\Pipelines\Exception\Pipeline\InvalidPipelinePayloadException;
use Klevu\Pipelines\Exception\TransformationExceptionInterface;
use Klevu\Pipelines\Extractor\Extractor;
use Klevu\Pipelines\Model\Extraction;
use Klevu\Pipelines\ObjectManager\Container;
use Klevu\Pipelines\Parser\ArgumentConverter;
use Klevu\Pipelines\Parser\SyntaxParser;
use Klevu\Pipelines\Pipeline\PipelineInterface;
use Klevu\Pipelines\Pipeline\StagesNotSupportedTrait;
use Klevu\Pipelines\Traits\ConvertIterableToArrayTrait;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SendBatchDeleteRequest implements PipelineInterface
{
    use StagesNotSupportedTrait;
    use ConvertIterableToArrayTrait;
    use InjectUserAgentProvidersTrait;

    final public const ARGUMENT_KEY_JS_API_KEY = 'jsApiKey';
    final public const ARGUMENT_KEY_REST_AUTH_KEY = 'restAuthKey';

    /**
     * @var Extractor
     */
    private readonly Extractor $extractor;
    /**
     * @var ArgumentConverter
     */
    private readonly ArgumentConverter $argumentConverter;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $jsApiKeyValidator;
    /**
     * @var ValidatorInterface
     */
    private readonly ValidatorInterface $restAuthKeyValidator;
    /**
     * @var BatchDeleteServiceInterface
     */
    private readonly BatchDeleteServiceInterface $batchDeleteService;
    /**
     * @var string
     */
    private readonly string $identifier;
    /**
     * @var string|Extraction
     */
    private string|Extraction $jsApiKeyArgument = '';
    /**
     * @var string|Extraction
     */
    private string|Extraction $restAuthKeyArgument = '';

    /**
     * @param Extractor|null $extractor
     * @param ArgumentConverter|null $argumentConverter
     * @param ValidatorInterface|null $jsApiKeyValidator
     * @param ValidatorInterface|null $restAuthKeyValidator
     * @param BatchDeleteServiceInterface|null $batchDeleteService
     * @param PipelineInterface[] $stages
     * @param mixed[]|null $args
     * @param string $identifier
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        ?Extractor $extractor = null,
        ?ArgumentConverter $argumentConverter = null,
        ?ValidatorInterface $jsApiKeyValidator = null,
        ?ValidatorInterface $restAuthKeyValidator = null,
        ?BatchDeleteServiceInterface $batchDeleteService = null,
        array $stages = [],
        ?array $args = null,
        string $identifier = '',
    ) {
        $container = Container::getInstance();

        $extractor ??= $container->get(Extractor::class);
        try {
            $this->extractor = $extractor; // @phpstan-ignore-line Invalid type covered by TypeError catch
        } catch (\TypeError) {
            throw new InvalidClassException(
                identifier: Extractor::class,
                instance: $extractor,
            );
        }

        $argumentConverter ??= $container->get(ArgumentConverter::class);
        try {
            $this->argumentConverter = $argumentConverter; // @phpstan-ignore-line Covered by TypeError catch
        } catch (\TypeError) {
            throw new InvalidClassException(
                identifier: ArgumentConverter::class,
                instance: $argumentConverter,
            );
        }

        $jsApiKeyValidator ??= $container->get(JsApiKeyValidator::class);
        try {
            $this->jsApiKeyValidator = $jsApiKeyValidator; // @phpstan-ignore-line Covered by TypeError catch
        } catch (\TypeError) {
            throw new InvalidClassException(
                identifier: JsApiKeyValidator::class,
                instance: $jsApiKeyValidator,
            );
        }

        $restAuthKeyValidator ??= $container->get(RestAuthKeyValidator::class);
        try {
            $this->restAuthKeyValidator = $restAuthKeyValidator; // @phpstan-ignore-line Covered by TypeError catch
        } catch (\TypeError) {
            throw new InvalidClassException(
                identifier: RestAuthKeyValidator::class,
                instance: $restAuthKeyValidator,
            );
        }

        $batchDeleteService ??= $container->get(BatchDeleteService::class);
        try {
            $this->batchDeleteService = $batchDeleteService; // @phpstan-ignore-line Covered by TypeError catch
        } catch (\TypeError) {
            throw new InvalidClassException(
                identifier: BatchDeleteService::class,
                instance: $batchDeleteService,
            );
        }

        $this->injectUserAgentProviders(
            // @phpstan-ignore-next-line Obviously not mixed given it's the class property
            userAgentProvider: $this->batchDeleteService->getUserAgentProvider(),
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
    public function setArgs(array $args): void
    {
        $this->jsApiKeyArgument = $this->prepareJsApiKeyArgument(
            jsApiKey: $args[self::ARGUMENT_KEY_JS_API_KEY] ?? null,
            arguments: $args,
        );
        $this->restAuthKeyArgument = $this->prepareRestAuthKeyArgument(
            restAuthKey: $args[self::ARGUMENT_KEY_REST_AUTH_KEY] ?? null,
            arguments: $args,
        );
    }

    /**
     * @param mixed $payload
     * @param \ArrayAccess<string|int, mixed>|null $context
     * @return ApiPipelineResult
     * @throws ExtractionExceptionInterface
     * @throws TransformationExceptionInterface
     */
    public function execute(
        mixed $payload,
        ?\ArrayAccess $context = null,
    ): ApiPipelineResult {
        $messages = [];
        try {
            $jsApiKey = $this->getJsApiKey(
                jsApiKeyArgument: $this->jsApiKeyArgument,
                payload: $payload,
                context: $context,
            );
            $restAuthKey = $this->getRestAuthKey(
                restAuthKeyArgument: $this->restAuthKeyArgument,
                payload: $payload,
                context: $context,
            );

            $apiResponse = $this->batchDeleteService->sendByIds(
                accountCredentials: new AccountCredentials(
                    jsApiKey: $jsApiKey,
                    restAuthKey: $restAuthKey,
                ),
                recordIds: $this->preparePayload($payload),
            );

            $success = $apiResponse->isSuccess();
            $messages = array_merge(
                $messages,
                $apiResponse->getMessages(),
                $apiResponse->errors ?? [],
            );
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

    /**
     * @param mixed $jsApiKey
     * @param mixed[] $arguments
     * @return string|Extraction
     */
    private function prepareJsApiKeyArgument(
        mixed $jsApiKey,
        array $arguments,
    ): string|Extraction {
        if (
            is_string($jsApiKey)
            && str_starts_with($jsApiKey, SyntaxParser::EXTRACTION_START_CHARACTER)
        ) {
            $jsApiKeyArgument = $this->argumentConverter->execute($jsApiKey);
            $jsApiKey = $jsApiKeyArgument->getValue();
        }

        if (!is_string($jsApiKey) && !($jsApiKey instanceof Extraction)) {
            throw new InvalidPipelineArgumentsException(
                pipelineName: $this::class,
                arguments: $arguments,
                message: sprintf(
                    'JS API Key argument (%s) must be string or extraction; Received %s',
                    self::ARGUMENT_KEY_JS_API_KEY,
                    get_debug_type($jsApiKey),
                ),
            );
        }

        return $jsApiKey;
    }

    /**
     * @param string|Extraction $jsApiKeyArgument
     * @param mixed $payload
     * @param \ArrayAccess<string|int, mixed>|null $context
     * @return string
     * @throws ExtractionExceptionInterface
     * @throws TransformationExceptionInterface
     */
    private function getJsApiKey(
        string|Extraction $jsApiKeyArgument,
        mixed $payload,
        ?\ArrayAccess $context,
    ): string {
        $jsApiKey = $jsApiKeyArgument;
        if ($jsApiKey instanceof Extraction) {
            $jsApiKey = $this->extractor->extract(
                source: $payload,
                accessor: $jsApiKey->accessor,
                transformations: $jsApiKey->transformations,
                context: $context,
            );
        }

        try {
            $this->jsApiKeyValidator->execute($jsApiKey);
        } catch (ValidationException $exception) {
            throw new InvalidPipelineArgumentsException(
                pipelineName: $this::class,
                arguments: [
                    self::ARGUMENT_KEY_JS_API_KEY => $jsApiKeyArgument,
                ],
                message: sprintf(
                    'JS API Key argument (%s): %s',
                    self::ARGUMENT_KEY_JS_API_KEY,
                    $exception->getMessage(),
                ),
            );
        }

        /** @var non-empty-string $jsApiKey - As handled by jsApiKeyValidator */
        return $jsApiKey;
    }

    /**
     * @param mixed $restAuthKey
     * @param mixed[] $arguments
     * @return string|Extraction
     */
    private function prepareRestAuthKeyArgument(
        mixed $restAuthKey,
        array $arguments,
    ): string|Extraction {
        if (
            is_string($restAuthKey)
            && str_starts_with($restAuthKey, SyntaxParser::EXTRACTION_START_CHARACTER)
        ) {
            $restAuthKeyArgument = $this->argumentConverter->execute($restAuthKey);
            $restAuthKey = $restAuthKeyArgument->getValue();
        }

        if (!is_string($restAuthKey) && !($restAuthKey instanceof Extraction)) {
            throw new InvalidPipelineArgumentsException(
                pipelineName: $this::class,
                arguments: $arguments,
                message: sprintf(
                    'REST AUTH Key argument (%s) must be string or extraction; Received %s',
                    self::ARGUMENT_KEY_JS_API_KEY,
                    get_debug_type($restAuthKey),
                ),
            );
        }

        return $restAuthKey;
    }

    /**
     * @param string|Extraction $restAuthKeyArgument
     * @param mixed $payload
     * @param \ArrayAccess<string|int, mixed>|null $context
     * @return string
     * @throws ExtractionExceptionInterface
     * @throws TransformationExceptionInterface
     */
    private function getRestAuthKey(
        string|Extraction $restAuthKeyArgument,
        mixed $payload,
        ?\ArrayAccess $context,
    ): string {
        $restAuthKey = $restAuthKeyArgument;
        if ($restAuthKey instanceof Extraction) {
            $restAuthKey = $this->extractor->extract(
                source: $payload,
                accessor: $restAuthKey->accessor,
                transformations: $restAuthKey->transformations,
                context: $context,
            );
        }

        try {
            $this->restAuthKeyValidator->execute($restAuthKey);
        } catch (ValidationException $exception) {
            throw new InvalidPipelineArgumentsException(
                pipelineName: $this::class,
                arguments: [
                    self::ARGUMENT_KEY_REST_AUTH_KEY => $restAuthKeyArgument,
                ],
                message: sprintf(
                    'REST AUTH Key argument (%s): %s',
                    self::ARGUMENT_KEY_REST_AUTH_KEY,
                    $exception->getMessage(),
                ),
            );
        }

        /** @var non-empty-string $restAuthKey - As handled by restAuthKeyValidator */
        return $restAuthKey;
    }

    /**
     * @param mixed $payload
     * @return string[]
     */
    private function preparePayload(mixed $payload): array
    {
        switch (true) {
            case null === $payload:
                $return = [];
                break;

            case is_string($payload):
                $return = [$payload];
                break;

            case !is_iterable($payload):
                throw new InvalidPipelinePayloadException(
                    pipelineName: $this::class,
                    message: sprintf(
                        'Stage: Indexing\SendBatchDelete Request expected payload of type '
                            . 'iterable<string|%s>; Received %s',
                        RecordInterface::class,
                        get_debug_type($payload),
                    ),
                );

            case $payload instanceof RecordIterator:
                $return = array_map(
                    static fn (RecordInterface $record): string => $record->getId(),
                    $payload->toArray(),
                );
                break;

            case $payload instanceof RecordInterface:
                $return = [$payload->getId()];
                break;


            default:
                $return = array_merge(
                    ...array_map(
                        [$this, 'preparePayload'],
                        $this->convertIterableToArray($payload),
                    ),
                );
                break;
        }

        return $return;
    }
}
