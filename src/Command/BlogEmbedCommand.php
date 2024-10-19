<?php

declare(strict_types=1);

namespace App\Command;

use Codewithkyrian\ChromaDB\Client;
use PhpLlm\LlmChain\Bridge\OpenAI\Embeddings;
use PhpLlm\LlmChain\Document\Vector;
use PhpLlm\LlmChain\Model\Response\AsyncResponse;
use PhpLlm\LlmChain\Model\Response\VectorResponse;
use PhpLlm\LlmChain\PlatformInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @phpstan-type Post array{
 *     id: Uuid,
 *     title: string,
 *     link: string,
 *     description: string,
 *     content: string,
 *     author: string,
 *     date: \DateTimeImmutable,
 * }
 */
#[AsCommand('app:blog:embed', description: 'Create embeddings for program data and push to ChromaDB.')]
final class BlogEmbedCommand extends Command
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly PlatformInterface $platform,
        private readonly Client $chromaClient,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Loading RSS of Symfony blog as embeddings into ChromaDB');

        $posts = $this->loadBlogPosts();
        $vectors = $this->createEmbeddings($posts);
        $this->pushToChromaDB($posts, $vectors);

        $io->success('Program Data Successfully Embedded!');

        return Command::SUCCESS;
    }

    /**
     * @return list<array{
     *     id: Uuid,
     *     title: string,
     *     link: string,
     *     description: string,
     *     content: string,
     *     author: string,
     *     date: \DateTimeImmutable,
     * }>
     */
    private function loadBlogPosts(): array
    {
        $response = $this->httpClient->request('GET', 'https://feeds.feedburner.com/symfony/blog');

        $posts = [];
        $crawler = new Crawler($response->getContent());
        $crawler->filter('item')->each(function (Crawler $node) use (&$posts) {
            $title = $node->filter('title')->text();
            $posts[] = [
                'id' => Uuid::v5(Uuid::fromString('6ba7b810-9dad-11d1-80b4-00c04fd430c8'), $title),
                'title' => $title,
                'link' => $node->filter('link')->text(),
                'description' => $node->filter('description')->text(),
                'content' => (new Crawler($node->filter('content\:encoded')->text()))->text(),
                'author' => $node->filter('dc\:creator')->text(),
                'date' => new \DateTimeImmutable($node->filter('pubDate')->text()),
            ];
        });

        return $posts;
    }

    /**
     * @param Post[] $posts
     *
     * @return Vector[]
     */
    private function createEmbeddings(array $posts): array
    {
        $texts = [];
        foreach ($posts as $post) {
            $texts[] = <<<TEXT
                Title: {$post['title']}
                From: {$post['author']} on {$post['date']->format('Y-m-d')}
                Description: {$post['description']}
                {$post['content']}
                TEXT;
        }

        $response = $this->platform->request(new Embeddings(), $texts);

        assert($response instanceof AsyncResponse);
        $response = $response->unwrap();
        assert($response instanceof VectorResponse);

        return $response->getContent();
    }

    /**
     * @param Post[] $posts
     * @param Vector[] $vectors
     */
    private function pushToChromaDB(array $posts, array $vectors): void
    {
        $collection = $this->chromaClient->getOrCreateCollection('symfony_blog');

        $ids = array_column($posts, 'id');
        $vectors = array_map(fn (Vector $vector) => $vector->getData(), $vectors);

        $collection->upsert($ids, $vectors, $posts);
    }
}
