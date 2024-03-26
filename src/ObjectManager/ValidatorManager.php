<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

/** @noinspection PhpRedundantOptionalArgumentInspection */

declare(strict_types=1);

namespace Klevu\PhpSDKPipelines\ObjectManager;

use Klevu\Pipelines\ObjectManager\ValidatorManager as BaseValidatorManager;
use Klevu\Pipelines\Validator\ValidatorInterface;

class ValidatorManager extends BaseValidatorManager
{
    /**
     * @param array<string, ValidatorInterface>|null $sharedInstances identifier => instance
     * @param array<string, int>|null $namespaces namespace => sort_order
     */
    public function __construct(
        ?array $sharedInstances = null,
        ?array $namespaces = null,
    ) {
        $this->registerNamespace(
            namespace: '\\Klevu\\PhpSDKPipelines\\Validator\\',
            sortOrder: static::DEFAULT_NAMESPACE_SORT_ORDER,
        );

        parent::__construct($sharedInstances, $namespaces);
    }
}
