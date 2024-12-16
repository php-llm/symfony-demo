<?php

declare(strict_types=1);

namespace App\YouTube;

use PhpLlm\LlmChain\ChainInterface;
use PhpLlm\LlmChain\Model\Message\Message;
use PhpLlm\LlmChain\Model\Message\MessageBag;
use PhpLlm\LlmChain\Model\Response\TextResponse;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

final class Chat
{
    private const SESSION_KEY = 'youtube-chat';

    public function __construct(
        private readonly RequestStack $requestStack,
        #[Autowire(service: 'llm_chain.chain.youtube')]
        private readonly ChainInterface $chain,
        private readonly TranscriptFetcher $transcriptFetcher,
    ) {
    }

    public function loadMessages(): MessageBag
    {
        return $this->requestStack->getSession()->get(self::SESSION_KEY, new MessageBag());
    }

    public function start(string $videoId): void
    {
        $this->reset();
        $messages = $this->loadMessages();

        $transcript = $this->transcriptFetcher->fetchTranscript($videoId);
        $system = <<<PROMPT
            You are an helpful assistant that answers questions about a YouTube video based on a transcript.
            If you can't answer a question, say so.
            
            Video ID: {$videoId}
            Transcript:
            {$transcript}
            PROMPT;

        $messages[] = Message::forSystem($system);
        $messages[] = Message::ofAssistant('What do you want to know about that video?');

        $this->saveMessages($messages);
    }

    public function submitMessage(string $message): void
    {
        $messages = $this->loadMessages();

        $messages[] = Message::ofUser($message);
        $response = $this->chain->call($messages);

        assert($response instanceof TextResponse);

        $messages[] = Message::ofAssistant($response->getContent());

        $this->saveMessages($messages);
    }

    public function reset(): void
    {
        $this->requestStack->getSession()->remove(self::SESSION_KEY);
    }

    private function saveMessages(MessageBag $messages): void
    {
        $this->requestStack->getSession()->set(self::SESSION_KEY, $messages);
    }
}
