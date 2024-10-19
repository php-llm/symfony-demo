<?php

declare(strict_types=1);

namespace App\YouTube;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TranscriptFetcher
{
    public function __construct(
        private readonly HttpClientInterface $client,
    ) {
    }

    public function fetchTranscript(string $videoId): string
    {
        // Fetch the HTML content of the YouTube video page
        $htmlResponse = $this->client->request('GET', 'https://youtube.com/watch?v='.$videoId);
        $html = $htmlResponse->getContent();

        // Use DomCrawler to parse the HTML
        $crawler = new Crawler($html);

        // Extract the script containing the ytInitialPlayerResponse
        $scriptContent = $crawler->filter('script')->reduce(function (Crawler $node) {
            return str_contains($node->text(), 'var ytInitialPlayerResponse = {');
        })->text();

        // Extract and parse the JSON data from the script
        $start = strpos($scriptContent, 'var ytInitialPlayerResponse = ') + strlen('var ytInitialPlayerResponse = ');
        $dataString = substr($scriptContent, $start);
        $dataString = substr($dataString, 0, strrpos($dataString, ';') ?: null);
        $data = json_decode(trim($dataString), true);

        // Extract the URL for the captions
        if (!isset($data['captions']['playerCaptionsTracklistRenderer']['captionTracks'][0]['baseUrl'])) {
            throw new \Exception('Captions are not available for this video.');
        }
        $captionsUrl = $data['captions']['playerCaptionsTracklistRenderer']['captionTracks'][0]['baseUrl'];

        // Fetch and parse the captions XML
        $xmlResponse = $this->client->request('GET', $captionsUrl);
        $xmlContent = $xmlResponse->getContent();
        $xmlCrawler = new Crawler($xmlContent);

        // Collect all text elements from the captions
        $transcript = $xmlCrawler->filter('text')->each(function (Crawler $node) {
            return $node->text().' ';
        });

        // Combine all the text elements into one string
        return implode(PHP_EOL, $transcript);
    }
}
