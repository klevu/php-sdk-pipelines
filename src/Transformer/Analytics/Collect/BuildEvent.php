<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDKPipelines\Transformer\Analytics\Collect;

use Klevu\PhpSDK\Exception\ValidationException;
use Klevu\PhpSDK\Model\Analytics\Collect\Event;
use Klevu\PhpSDK\Model\Analytics\Collect\EventType;
use Klevu\PhpSDK\Model\Analytics\Collect\UserProfile;
use Klevu\PhpSDK\Validator\JsApiKeyValidator;
use Klevu\PhpSDK\Validator\ValidatorInterface;
use Klevu\Pipelines\Exception\ExtractionException;
use Klevu\Pipelines\Exception\ObjectManager\InvalidClassException;
use Klevu\Pipelines\Exception\Pipeline\InvalidPipelinePayloadException;
use Klevu\Pipelines\Exception\Transformation\InvalidTransformationArgumentsException;
use Klevu\Pipelines\Extractor\Extractor;
use Klevu\Pipelines\Model\Argument;
use Klevu\Pipelines\Model\ArgumentIterator;
use Klevu\Pipelines\Model\Extraction;
use Klevu\Pipelines\ObjectManager\Container;
use Klevu\Pipelines\Transformer\TransformerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class BuildEvent implements TransformerInterface
{
    final public const ARGUMENT_INDEX_EVENT_TYPE = 0;
    final public const ARGUMENT_INDEX_APIKEY = 1;
    final public const ARGUMENT_INDEX_EVENT_VERSION = 2;
    final public const ARGUMENT_INDEX_USER_PROFILE = 3;

    /**
     * @var ValidatorInterface|JsApiKeyValidator
     */
    private ValidatorInterface $apikeyValidator;
    /**
     * @var Extractor
     */
    private Extractor $extractor;

    /**
     * @param ValidatorInterface|null $apikeyValidator
     * @param Extractor|null $extractor
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        ?ValidatorInterface $apikeyValidator = null,
        ?Extractor $extractor = null,
    ) {
        $container = Container::getInstance();

        if (null === $apikeyValidator) {
            $apikeyValidator = $container->get(JsApiKeyValidator::class);
            if (!($apikeyValidator instanceof ValidatorInterface)) {
                throw new InvalidClassException(
                    identifier: JsApiKeyValidator::class,
                    instance: $apikeyValidator, // @phpstan-ignore-line Variable is clearly an object at this stage
                );
            }
        }
        $this->apikeyValidator = $apikeyValidator;
        if (null === $extractor) {
            $extractor = $container->get(Extractor::class);
            if (!($extractor instanceof Extractor)) {
                throw new InvalidClassException(
                    identifier: Extractor::class,
                    instance: $extractor, // @phpstan-ignore-line Variable is clearly an object at this stage
                );
            }
        }
        $this->extractor = $extractor;
    }

    /**
     * @param mixed $data
     * @param ArgumentIterator|null $arguments
     * @param \ArrayAccess<string|int, mixed>|null $context
     * @return mixed
     * @throws InvalidPipelinePayloadException
     */
    public function transform(
        mixed $data,
        ?ArgumentIterator $arguments = null,
        ?\ArrayAccess $context = null,
    ): mixed {
        if (null === $data) {
            return null;
        }

        if (!is_array($data)) {
            throw new InvalidPipelinePayloadException(
                pipelineName: $this::class,
                message: sprintf(
                    'Stage: BuildEvent expects payload of type array; Received: %s',
                    get_debug_type($data),
                ),
            );
        }

        $arguments = $this->prepareArguments($arguments);

        $eventTypeArgument = $arguments->getByKey(self::ARGUMENT_INDEX_EVENT_TYPE);
        if ($eventTypeArgument?->getValue() instanceof Extraction) {
            $eventTypeArgument = new Argument(
                value: $this->extractor->extract(
                    source: $data,
                    accessor: $eventTypeArgument->getValue()->accessor,
                    context: $context,
                ),
                key: self::ARGUMENT_INDEX_EVENT_TYPE,
            );

            $eventTypeArgument = $this->prepareEventTypeArgument(
                eventTypeArgument: $eventTypeArgument,
                arguments: $arguments,
            );
        }
        /** @var EventType $eventTypeValue */
        $eventTypeValue = $eventTypeArgument?->getValue();

        $apikeyArgument = $arguments->getByKey(self::ARGUMENT_INDEX_APIKEY);
        if ($apikeyArgument?->getValue() instanceof Extraction) {
            $apikeyArgument = new Argument(
                value: $this->extractor->extract(
                    source: $data,
                    accessor: $apikeyArgument->getValue()->accessor,
                    context: $context,
                ),
                key: self::ARGUMENT_INDEX_APIKEY,
            );

            $apikeyArgument = $this->prepareApikeyArgument(
                apikeyArgument: $apikeyArgument,
                arguments: $arguments,
            );
        }
        /** @var string $apikeyValue */
        $apikeyValue = $apikeyArgument?->getValue();

        $eventVersionArgument = $arguments->getByKey(self::ARGUMENT_INDEX_EVENT_VERSION);
        if ($eventVersionArgument?->getValue() instanceof Extraction) {
            $eventVersionArgument = new Argument(
                value: $this->extractor->extract(
                    source: $data,
                    accessor: $eventVersionArgument->getValue()->accessor,
                    context: $context,
                ),
                key: self::ARGUMENT_INDEX_EVENT_VERSION,
            );

            $eventVersionArgument = $this->prepareEventVersionArgument(
                eventVersionArgument: $eventVersionArgument,
                arguments: $arguments,
            );
        }
        /** @var string $eventVersionValue */
        $eventVersionValue = $eventVersionArgument?->getValue();

        $userProfileArgument = $arguments->getByKey(self::ARGUMENT_INDEX_USER_PROFILE);
        $userProfile = $this->processUserProfileValue(
            userProfileValue: $userProfileArgument?->getValue(),
            payload: $data,
            arguments: $arguments,
            context: $context,
        );

        return new Event(
            event: $eventTypeValue,
            apikey: $apikeyValue,
            version: $eventVersionValue,
            data: $data,
            userProfile: $userProfile,
        );
    }

    /**
     * @param ArgumentIterator|null $arguments
     * @return ArgumentIterator
     */
    private function prepareArguments(?ArgumentIterator $arguments): ArgumentIterator
    {
        if (null === $arguments) {
            $arguments = new ArgumentIterator();
        }
        $return = new ArgumentIterator();

        $eventTypeArgument = $arguments->getByKey(self::ARGUMENT_INDEX_EVENT_TYPE)
            ?? new Argument(
                value: null,
                key: self::ARGUMENT_INDEX_EVENT_TYPE,
            );
        $return->addItem(
            $this->prepareEventTypeArgument(
                eventTypeArgument: $eventTypeArgument,
                arguments: $arguments,
            ),
        );

        $apikeyArgument = $arguments->getByKey(self::ARGUMENT_INDEX_APIKEY)
            ?? new Argument(
                value: null,
                key: self::ARGUMENT_INDEX_APIKEY,
            );
        $return->addItem(
            $this->prepareApikeyArgument(
                apikeyArgument: $apikeyArgument,
                arguments: $arguments,
            ),
        );

        $eventVersionArgument = $arguments->getByKey(self::ARGUMENT_INDEX_EVENT_VERSION)
            ?? new Argument(
                value: null,
                key: self::ARGUMENT_INDEX_EVENT_VERSION,
            );
        $return->addItem(
            $this->prepareEventVersionArgument(
                eventVersionArgument: $eventVersionArgument,
                arguments: $arguments,
            ),
        );

        $userProfileArgument = $arguments->getByKey(self::ARGUMENT_INDEX_USER_PROFILE)
            ?? new Argument(
                value: null,
                key: self::ARGUMENT_INDEX_USER_PROFILE,
            );
        $return->addItem(
            $this->prepareUserProfileArgument(
                userProfileArgument: $userProfileArgument,
                arguments: $arguments,
            ),
        );

        return $return;
    }

    /**
     * @param Argument $eventTypeArgument
     * @param ArgumentIterator|null $arguments
     * @return Argument
     * @throws InvalidTransformationArgumentsException
     */
    private function prepareEventTypeArgument(
        Argument $eventTypeArgument,
        ?ArgumentIterator $arguments,
    ): Argument {
        $eventTypeValue = $eventTypeArgument->getValue();

        switch (true) {
            case empty($eventTypeValue):
                throw new InvalidTransformationArgumentsException(
                    transformerName: $this::class,
                    errors: [
                        sprintf(
                            'Event Type argument (%s) is required',
                            self::ARGUMENT_INDEX_EVENT_TYPE,
                        ),
                    ],
                    arguments: $arguments,
                );

            case $eventTypeValue instanceof EventType:
            case $eventTypeValue instanceof Extraction:
                break;

            case is_string($eventTypeValue):
                try {
                    $eventTypeArgument->setValue(EventType::from($eventTypeValue));
                } catch (\ValueError $exception) {
                    throw new InvalidTransformationArgumentsException(
                        transformerName: $this::class,
                        errors: [
                            sprintf(
                                'Unrecognised Event Type argument (%s) value: %s',
                                self::ARGUMENT_INDEX_EVENT_TYPE,
                                $eventTypeValue,
                            ),
                        ],
                        arguments: $arguments,
                        previous: $exception,
                    );
                }
                break;

            default:
                throw new InvalidTransformationArgumentsException(
                    transformerName: $this::class,
                    errors: [
                        sprintf(
                            'Invalid Event Type argument (%s)',
                            self::ARGUMENT_INDEX_EVENT_TYPE,
                        ),
                    ],
                    arguments: $arguments,
                );
        }

        return $eventTypeArgument;
    }

    /**
     * @param Argument $apikeyArgument
     * @param ArgumentIterator|null $arguments
     * @return Argument
     * @throws InvalidTransformationArgumentsException
     */
    private function prepareApikeyArgument(
        Argument $apikeyArgument,
        ?ArgumentIterator $arguments,
    ): Argument {
        $apikeyValue = $apikeyArgument->getValue();

        if ($apikeyValue instanceof Extraction) {
            return $apikeyArgument;
        }

        try {
            $this->apikeyValidator->execute($apikeyValue);
        } catch (ValidationException $exception) {
            throw new InvalidTransformationArgumentsException(
                transformerName: $this::class,
                errors: [
                    sprintf(
                        'Apikey argument (%s) is not a valid Klevu API Key: %s',
                        self::ARGUMENT_INDEX_APIKEY,
                        is_scalar($apikeyValue)
                            ? $apikeyValue
                            : get_debug_type($apikeyValue),
                    ),
                ],
                arguments: $arguments,
                previous: $exception,
            );
        }

        return $apikeyArgument;
    }

    /**
     * @param Argument $eventVersionArgument
     * @param ArgumentIterator|null $arguments
     * @return Argument
     * @throws InvalidTransformationArgumentsException
     */
    private function prepareEventVersionArgument(
        Argument $eventVersionArgument,
        ?ArgumentIterator $arguments,
    ): Argument {
        $eventVersionValue = $eventVersionArgument->getValue();

        switch (true) {
            case empty($eventVersionValue):
                throw new InvalidTransformationArgumentsException(
                    transformerName: $this::class,
                    errors: [
                        sprintf(
                            'Event Version argument (%s) is required',
                            self::ARGUMENT_INDEX_EVENT_VERSION,
                        ),
                    ],
                    arguments: $arguments,
                );

            case $eventVersionValue instanceof Extraction:
            case is_string($eventVersionValue): // Let later validation handle semver specifics
                break;

            default:
                throw new InvalidTransformationArgumentsException(
                    transformerName: $this::class,
                    errors: [
                        sprintf(
                            'Invalid Event Version argument (%s): %s',
                            self::ARGUMENT_INDEX_EVENT_TYPE,
                            get_debug_type($eventVersionValue),
                        ),
                    ],
                    arguments: $arguments,
                );
        }

        return $eventVersionArgument;
    }

    /**
     * @param Argument $userProfileArgument
     * @param ArgumentIterator|null $arguments
     * @return Argument
     */
    private function prepareUserProfileArgument(
        Argument $userProfileArgument,
        ?ArgumentIterator $arguments,
    ): Argument {
        $userProfileValue = $userProfileArgument->getValue();

        switch (true) {
            case empty($userProfileValue):
                $userProfileArgument->setValue(null);
                break;

            case $userProfileValue instanceof Extraction:
            case $userProfileValue instanceof UserProfile:
            case is_array($userProfileValue):
            case is_object($userProfileValue):
                break;

            default:
                throw new InvalidTransformationArgumentsException(
                    transformerName: $this::class,
                    errors: [
                        sprintf(
                            'Invalid User Profile argument (%s): %s',
                            self::ARGUMENT_INDEX_USER_PROFILE,
                            get_debug_type($userProfileValue),
                        ),
                    ],
                    arguments: $arguments,
                );
        }

        return $userProfileArgument;
    }

    /**
     * @param mixed $userProfileValue
     * @param mixed $payload
     * @param ArgumentIterator|null $arguments
     * @param \ArrayAccess<string|int, mixed>|null $context
     * @return UserProfile|null
     */
    private function processUserProfileValue(
        mixed $userProfileValue,
        mixed $payload,
        ?ArgumentIterator $arguments,
        ?\ArrayAccess $context,
    ): ?UserProfile {
        if (null === $userProfileValue || $userProfileValue instanceof UserProfile) {
            return $userProfileValue;
        }

        if ($userProfileValue instanceof Extraction) {
            return $this->processUserProfileValue(
                userProfileValue: $this->extractor->extract(
                    source: $payload,
                    accessor: $userProfileValue->accessor,
                    transformations: $userProfileValue->transformations,
                    context: $context,
                ),
                payload: $payload,
                arguments: $arguments,
                context: $context,
            );
        }

        try {
            $ipAddress = $this->extractor->extract(
                source: $userProfileValue,
                accessor: 'ip_address',
            );
            $email = $this->extractor->extract(
                source: $userProfileValue,
                accessor: 'email',
            );

            $userProfile = new UserProfile(
                ipAddress: $ipAddress ?: null, // @phpstan-ignore-line Possible invalid type handled by TypeError catch
                email: $email ?: null, // @phpstan-ignore-line Possible invalid type handled by TypeError catch
            );
        } catch (\TypeError | ExtractionException $exception) {
            throw new InvalidTransformationArgumentsException(
                transformerName: $this::class,
                errors: [
                    sprintf(
                        'Invalid User Profile argument (%s): %s',
                        self::ARGUMENT_INDEX_USER_PROFILE,
                        $exception->getMessage(),
                    ),
                ],
                arguments: $arguments,
            );
        }

        return $userProfile;
    }
}
