<?php

declare(strict_types=1);

/*
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 *  Copyright (C) 2019 - 2023 Jan Böhmer (https://github.com/jbtronics)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ScanControllerTest extends WebTestCase
{

    private ?KernelBrowser $client = null;

    public function setUp(): void
    {
        $this->client = static::createClient([], [
            'PHP_AUTH_USER' => 'admin',
            'PHP_AUTH_PW' => 'test',
        ]);
        $this->client->disableReboot();
        $this->client->catchExceptions(false);
    }

    public function testRedirectOnInputParameter(): void
    {
        $this->client->request('GET', '/en/scan?input=0000001');
        $this->assertResponseRedirects('/en/part/1');
    }

    public function testStocktakeModeCanBeStartedFromTheQueryString(): void
    {
        $crawler = $this->client->request('GET', '/en/scan?stocktake=1');
        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('input#scan_dialog_stocktake_mode[checked]'));
        $this->assertCount(1, $crawler->filter('a[href="/en/scan"]'));
    }

    public function testStocktakeModeShowsTheCountFormInsteadOfRedirecting(): void
    {
        $crawler = $this->client->request('GET', '/en/scan');
        $form = $crawler->filter('#scan_dialog_form')->form([
            'scan_dialog[input]' => '0000001',
            'scan_dialog[stocktake_mode]' => true,
        ]);
        $crawler = $this->client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('#scan-stocktake'));
        $this->assertCount(1, $crawler->filter('input#scan_dialog_stocktake_mode[checked]'));
    }

    public function testScanQRCode(): void
    {
        $this->client->request('GET', '/scan/part/1');
        $this->assertResponseRedirects('/en/part/1');
    }
}
