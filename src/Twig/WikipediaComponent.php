<?php

declare(strict_types=1);

namespace App\Twig;

use App\Chat\Wikipedia;
use PhpLlm\LlmChain\Model\Message\MessageBag;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('wikipedia')]
final class WikipediaComponent
{
    use DefaultActionTrait;

    public function __construct(
        private readonly Wikipedia $wikipedia,
    ) {
    }

    public function getMessages(): MessageBag
    {
        return $this->wikipedia->loadMessages()->withoutSystemMessage();
    }

    #[LiveAction]
    public function submit(#[LiveArg] string $message): void
    {
        $this->wikipedia->submitMessage($message);
    }

    #[LiveAction]
    public function reset(): void
    {
        $this->wikipedia->reset();
    }
}
