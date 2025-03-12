<?php

declare(strict_types=1);

namespace App\Audio;

use PhpLlm\LlmChain\Bridge\OpenAI\Whisper;
use PhpLlm\LlmChain\Bridge\OpenAI\Whisper\File;
use PhpLlm\LlmChain\ChainInterface;
use PhpLlm\LlmChain\Model\Message\Message;
use PhpLlm\LlmChain\Model\Message\MessageBag;
use PhpLlm\LlmChain\Model\Response\AsyncResponse;
use PhpLlm\LlmChain\Model\Response\TextResponse;
use PhpLlm\LlmChain\PlatformInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

final class Chat
{
    private const SESSION_KEY = 'audio-chat';

    public function __construct(
        private readonly PlatformInterface $platform,
        private readonly RequestStack $requestStack,
        #[Autowire(service: 'llm_chain.chain.audio')]
        private readonly ChainInterface $chain,
    ) {
    }

    public function say(string $base64audio): void
    {
        // Convert base64 to temporary binary file
        $path = tempnam(sys_get_temp_dir(), 'audio-').'.wav';
        file_put_contents($path, base64_decode($base64audio));

        $response = $this->platform->request(new Whisper(), new File($path));
        assert($response instanceof AsyncResponse);
        $response = $response->unwrap();
        assert($response instanceof TextResponse);

        $this->submitMessage($response->getContent());
    }

    public function loadMessages(): MessageBag
    {
        return $this->requestStack->getSession()->get(self::SESSION_KEY, new MessageBag());
    }

    public function submitMessage(string $message): void
    {
        $messages = $this->loadMessages();

        $messages->add(Message::ofUser($message));
        $response = $this->chain->call($messages);

        assert($response instanceof TextResponse);

        $messages->add(Message::ofAssistant($response->getContent()));

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
