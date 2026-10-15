<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[Group('DB')]
#[Group('slow')]
final class HomepageControllerTest extends WebTestCase
{
    public function testHomepageShowsUpstreamVersionAndLinkedForkBuildNumber(): void
    {
        $previousBuildVersion = getenv('PARTDB_BUILD_VERSION');
        putenv('PARTDB_BUILD_VERSION=2.20.1-5');

        try {
            $client = self::createClient();
            $crawler = $client->request('GET', '/en/');

            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('[data-build-identity]', 'Version v2.20.1');
            self::assertSelectorTextContains('[data-build-changelog]', 'Build 5');
            $buildLink = $crawler->filter('[data-build-changelog]');
            self::assertSame(
                'https://github.com/AGL-Electronics/Part-DB-server/compare/2.20.1-4...2.20.1-5',
                $buildLink->attr('href')
            );
            self::assertSame('View changes in build 5', $buildLink->attr('title'));
        } finally {
            putenv(false === $previousBuildVersion
                ? 'PARTDB_BUILD_VERSION'
                : 'PARTDB_BUILD_VERSION='.$previousBuildVersion);
        }
    }
}
