<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

/** @noinspection PhpRedundantOptionalArgumentInspection */

declare(strict_types=1);

namespace Klevu\PhpSDKPipelines\ObjectManager;

use Klevu\Pipelines\ObjectManager\TransformerManager as BaseTransformerManager;
use Klevu\Pipelines\Transformer\TransformerInterface;

class TransformerManager extends BaseTransformerManager
{
    /**
     * @param array<string, TransformerInterface>|null $sharedInstances identifier => instance
     * @param array<string, int>|null $namespaces namespace => sort_order
     */
    public function __construct(
        ?array $sharedInstances = null,
        ?array $namespaces = null,
    ) {
        $this->registerNamespace(
            namespace: '\\Klevu\\PhpSDKPipelines\\Transformer\\',
            sortOrder: static::DEFAULT_NAMESPACE_SORT_ORDER,
        );

        parent::__construct($sharedInstances, $namespaces);
    }
}
