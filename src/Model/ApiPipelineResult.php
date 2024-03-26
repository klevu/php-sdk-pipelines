<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDKPipelines\Model;

use Klevu\PhpSDK\Api\Model\ApiResponseInterface;

class ApiPipelineResult
{
    /**
     * @param bool $success
     * @param mixed|null $payload
     * @param string[] $messages
     * @param ApiResponseInterface|null $apiResponse
     */
    public function __construct(
        public readonly bool $success,
        public readonly mixed $payload = null,
        public readonly array $messages = [],
        public readonly ?ApiResponseInterface $apiResponse = null,
    ) {
    }
}
