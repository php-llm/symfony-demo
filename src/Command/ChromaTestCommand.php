<?php

declare(strict_types=1);

namespace App\Command;

use Codewithkyrian\ChromaDB\Client;
use PhpLlm\LlmChain\Bridge\OpenAI\Embeddings;
use PhpLlm\LlmChain\Model\Response\AsyncResponse;
use PhpLlm\LlmChain\Model\Response\VectorResponse;
use PhpLlm\LlmChain\PlatformInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('app:chroma:test', description: 'Testing Chroma DB connection.')]
final class ChromaTestCommand extends Command
{
    public function __construct(
        private readonly Client $chromaClient,
        private readonly PlatformInterface $platform,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Testing Chroma DB Connection');

        $io->comment('Connecting to Chroma DB ...');

        // Check current ChromaDB version
        $version = $this->chromaClient->version();

        // Get WSC Collection
        $collection = $this->chromaClient->getOrCreateCollection('symfony_blog');

        $io->table(['Key', 'Value'], [
            ['ChromaDB Version', $version],
            ['Collection Name', $collection->name],
            ['Collection ID', $collection->id],
            ['Total Documents', $collection->count()],
        ]);

        $io->comment('Searching for content about "New Symfony Features" ...');

        $platformResponse = $this->platform->request(new Embeddings(), 'New Symfony Features');
        assert($platformResponse instanceof AsyncResponse);
        $platformResponse = $platformResponse->unwrap();
        assert($platformResponse instanceof VectorResponse);
        $queryResponse = $collection->query(
            queryEmbeddings: [$platformResponse->getContent()[0]->getData()],
            nResults: 4,
        );

        if (1 === count($queryResponse->ids, COUNT_RECURSIVE)) {
            $io->error('No results found!');

            return Command::FAILURE;
        }

        $io->table(['ID', 'Title'], [
            /* @phpstan-ignore-next-line */
            [$queryResponse->ids[0][0], $queryResponse->metadatas[0][0]['title']],
            /* @phpstan-ignore-next-line */
            [$queryResponse->ids[0][1], $queryResponse->metadatas[0][1]['title']],
            /* @phpstan-ignore-next-line */
            [$queryResponse->ids[0][2], $queryResponse->metadatas[0][2]['title']],
            /* @phpstan-ignore-next-line */
            [$queryResponse->ids[0][3], $queryResponse->metadatas[0][3]['title']],
        ]);

        $io->success('Chroma DB Connection & Similarity Search Test Successful!');

        return Command::SUCCESS;
    }
}
