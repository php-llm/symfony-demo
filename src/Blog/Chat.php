<?php

declare(strict_types=1);

namespace App\Blog;

use PhpLlm\LlmChain\ChainInterface;
use PhpLlm\LlmChain\Model\Message\Message;
use PhpLlm\LlmChain\Model\Message\MessageBag;
use PhpLlm\LlmChain\Model\Response\TextResponse;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

final class Chat
{
    private const SESSION_KEY = 'blog-chat';

    public function __construct(
        private readonly RequestStack $requestStack,
        #[Autowire(service: 'llm_chain.chain.blog')]
        private readonly ChainInterface $chain,
    ) {
    }

    public function loadMessages(): MessageBag
    {
        $messages = new MessageBag(
            Message::forSystem(<<<PROMPT
                You are an helpful assistant that knows about the latest blog content of the Symfony's framework website.
                To search for content you use the tool 'similarity_search' for generating the answer. Only use content
                that you get from searching with that tool or you previous answers. Don't make up information and if you
                can't find something, just say so. Also provide links to the blog posts you use as sources.
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
