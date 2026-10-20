<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Tests\TestAsset\Settings;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Settings\SettingsInterface;
use Contenir\Extension\Api\Url\UrlResolverInterface;
use LogicException;
use Override;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * A context that offers the Tracking section's settings and nothing else,
 * for the add-on's code that only reads and writes them.
 */
final readonly class SettingsContext implements ExtensionContextInterface
{
    public function __construct(
        private SettingsInterface $settings,
    ) {}

    #[Override]
    public function clock(): ClockInterface
    {
        throw new LogicException('Not offered');
    }

    #[Override]
    public function events(): EventDispatcherInterface
    {
        throw new LogicException('Not offered');
    }

    #[Override]
    public function handle(): Handle
    {
        return new Handle('tracking');
    }

    #[Override]
    public function logger(): LoggerInterface
    {
        throw new LogicException('Not offered');
    }

    #[Override]
    public function services(): ContainerInterface
    {
        throw new LogicException('Not offered');
    }

    #[Override]
    public function settings(?string $section = null): SettingsInterface
    {
        return $this->settings;
    }

    #[Override]
    public function sitePath(): string
    {
        throw new LogicException('Not offered');
    }

    #[Override]
    public function urls(): UrlResolverInterface
    {
        throw new LogicException('Not offered');
    }
}
