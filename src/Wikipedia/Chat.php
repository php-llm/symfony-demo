<?php

declare(strict_types=1);

namespace App\Wikipedia;

use PhpLlm\LlmChain\ChainInterface;
use PhpLlm\LlmChain\Model\Message\Message;
use PhpLlm\LlmChain\Model\Message\MessageBag;
use PhpLlm\LlmChain\Model\Response\TextResponse;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

final class Chat
{
    private const SESSION_KEY = 'wikipedia-chat';

    public function __construct(
        private readonly RequestStack $requestStack,
        #[Autowire(service: 'llm_chain.chain.wikipedia')]
        private readonly ChainInterface $chain,
    ) {
    }

    public function loadMessages(): MessageBag
    {
        $default = new MessageBag();
        $default[] = Message::forSystem('Please answer the users question based on Wikipedia and provide a link to the article.');

        return $this->requestStack->getSession()->get(self::SESSION_KEY, $default);
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
