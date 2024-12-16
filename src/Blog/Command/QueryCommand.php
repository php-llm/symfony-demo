<?php

declare(strict_types=1);

namespace App\Blog\Command;

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

#[AsCommand('app:blog:query', description: 'Test command for querying the blog collection in Chroma DB.')]
final class QueryCommand extends Command
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
        $collection = $this->chromaClient->getOrCreateCollection('symfony_blog');
        $io->table(['Key', 'Value'], [
            ['ChromaDB Version', $this->chromaClient->version()],
            ['Collection Name', $collection->name],
            ['Collection ID', $collection->id],
            ['Total Documents', $collection->count()],
        ]);

        $search = $io->ask('What do you want to know about?', 'New Symfony Features');
        $io->comment(sprintf('Converting "%s" to vector & searching in Chroma DB ...', $search));
        $io->comment('Results are limited to 4 most similar documents.');

        $platformResponse = $this->platform->request(new Embeddings(), $search);
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

        foreach ($queryResponse->ids[0] as $i => $id) {
            /* @phpstan-ignore-next-line */
            $io->section($queryResponse->metadatas[0][$i]['title']);
            /* @phpstan-ignore-next-line */
            $io->block($queryResponse->metadatas[0][$i]['description']);
        }

        $io->success('Chroma DB Connection & Similarity Search Test Successful!');

        return Command::SUCCESS;
    }
}
