<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDKPipelines\Provider\UserAgent\SystemInformation;

use Composer\InstalledVersions; // phpcs:ignore SlevomatCodingStandard.Namespaces.UseOnlyWhitelistedNamespaces.NonFullyQualified, Generic.Files.LineLength.TooLong
use Klevu\PhpSDK\Provider\UserAgentProviderInterface;

class PhpSDKPipelinesProvider implements UserAgentProviderInterface
{
    final public const PRODUCT_NAME = 'klevu-php-sdk-pipelines';

    /**
     * @return string
     */
    public function execute(): string
    {
        try {
            $version = InstalledVersions::getVersion('klevu/php-sdk-pipelines');
        } catch (\OutOfBoundsException) {
            $version = null;
        }

        return $version
            ? sprintf('%s/%s', self::PRODUCT_NAME, $version)
            : self::PRODUCT_NAME;
    }
}
