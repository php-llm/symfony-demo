<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;

#[CoversNothing]
final class SmokeTest extends WebTestCase
{
    use InteractsWithLiveComponents;

    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Welcome');
        self::assertSelectorCount(3, '.card');
    }

    public function testRag(): void
    {
        $client = static::createClient();
        $client->request('GET', '/blog');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h4', 'Retrieval Augmented Generation with the Symfony blog');
        self::assertSelectorCount(1, '#chat-submit');
    }

    public function testYouTube(): void
    {
        $client = static::createClient();
        $client->request('GET', '/youtube');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h4', 'Chat about a YouTube Video');
        self::assertSelectorCount(1, '#chat-submit');
    }

    public function testWikipedia(): void
    {
        $client = static::createClient();
        $client->request('GET', '/wikipedia');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h4', 'Wikipedia Research');
        self::assertSelectorCount(1, '#chat-submit');
    }
}
