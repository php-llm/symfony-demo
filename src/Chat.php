<?php

declare(strict_types=1);

namespace App;

use PhpLlm\LlmChain\ChainInterface;
use PhpLlm\LlmChain\Model\Message\Message;
use PhpLlm\LlmChain\Model\Message\MessageBag;
use Symfony\Component\HttpFoundation\RequestStack;

final class Chat
{
    private const SESSION_KEY = 'chat-messages';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ChainInterface $chain,
    ) {
    }

    public function loadMessages(): MessageBag
    {
        $messages = new MessageBag(
            Message::forSystem(<<<PROMPT
                You are an helpful assistant and answer questions about the Web Summer Camp conference.
                You use the tool 'similarity_search' to search for information that you use for generating the answer.
                Don't make up information and if you can't find something, just say so.
                PROMPT
            )
        );

        return $this->requestStack->getSession()->get(self::SESSION_KEY, $messages);
    }

    public function submitMessage(string $message): void
    {
        $messages = $this->loadMessages();

        $messages[] = Message::ofUser($message);
        $response = $this->chain->call($messages);
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
