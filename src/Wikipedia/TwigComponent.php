<?php

declare(strict_types=1);

namespace App\Wikipedia;

use PhpLlm\LlmChain\Model\Message\MessageBag;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('wikipedia', template: 'wikipedia/component.html.twig')]
final class TwigComponent
{
    use DefaultActionTrait;

    public function __construct(
        private readonly Chat $wikipedia,
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
