<?php

/**
 * Copyright © Klevu Oy. All rights reserved. See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace Klevu\PhpSDKPipelines\Test\Unit\Provider\UserAgent\SystemInformation;

use Klevu\PhpSDKPipelines\Provider\UserAgent\SystemInformation\PhpPipelinesProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpPipelinesProvider::class)]
class PhpPipelinesUserAgentProviderTest extends TestCase
{
    #[Test]
    public function testExecute(): void
    {
        $phpSDKPipelinesUserAgentProvider = new PhpPipelinesProvider();

        $this->assertSame(
            expected: 'klevu-php-pipelines/' . $this->getLibraryVersion('php-pipelines'),
            actual: $phpSDKPipelinesUserAgentProvider->execute(),
        );
    }

    /**
     * @param string $library
     * @return string
     */
    private function getLibraryVersion(string $library): string
    {
        $composerFilename = sprintf('%s/../../../../../../%s/composer.json', __DIR__, $library);
        $composerContent = json_decode(
            json: file_get_contents($composerFilename) ?: '{}',
            associative: true,
        );
        if (!is_array($composerContent)) {
            $composerContent = [];
        }

        $version = $composerContent['version'] ?? '-';
        $versionParts = explode('.', $version) + array_fill(0, 4, '0');

        return implode('.', $versionParts);
    }
}
