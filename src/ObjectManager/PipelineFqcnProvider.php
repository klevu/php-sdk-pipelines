<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

/** @noinspection PhpRedundantOptionalArgumentInspection */

declare(strict_types=1);

namespace Klevu\PhpSDKPipelines\ObjectManager;

use Klevu\Pipelines\ObjectManager\ObjectManagerInterface;
use Klevu\Pipelines\ObjectManager\PipelineFqcnProvider as BasePipelineFqcnProvider;

class PipelineFqcnProvider extends BasePipelineFqcnProvider
{
    /**
     * @param string[] $aliasToFqcn
     * @param array<string, int> $namespaces
     */
    public function __construct(
        array $aliasToFqcn = [],
        array $namespaces = [],
    ) {
        $this->registerNamespace(
            namespace: '\\Klevu\\PhpSDKPipelines\\Pipeline\\',
            sortOrder: ObjectManagerInterface::DEFAULT_NAMESPACE_SORT_ORDER,
        );

        parent::__construct($aliasToFqcn, $namespaces);
    }
}
