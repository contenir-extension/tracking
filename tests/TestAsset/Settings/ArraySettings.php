<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Tests\TestAsset\Settings;

use Contenir\Extension\Api\Event\ActionResult;
use Contenir\Extension\Api\Event\Outcome;
use Contenir\Extension\Api\Exception\UnknownSettingException;
use Contenir\Extension\Api\Form\Field;
use Contenir\Extension\Api\Handle;
use Contenir\Extension\Api\Settings\SettingsInterface;
use Contenir\Extension\Tracking\TrackingSection;
use Override;

use function array_key_exists;

/**
 * The Tracking section's settings held in an array: saved values over the
 * declared defaults, as a host reads them. set() records what it saved and
 * answers with the outcome it was given.
 */
final class ArraySettings implements SettingsInterface
{
    /**
     * @var array<string, mixed>
     */
    public array $saved = [];

    /**
     * @param array<string, mixed> $values By field name
     */
    public function __construct(
        private array $values = [],
        private Outcome $outcome = Outcome::Done,
        private string $message = '',
    ) {}

    /**
     * @return list<Field>
     */
    private static function fields(): array
    {
        return TrackingSection::define(new Handle('tracking'))->form->fields;
    }

    #[Override]
    public function all(): array
    {
        $defaults = [];
        foreach (self::fields() as $field) {
            $defaults[$field->name] = $field->default;
        }

        return [...$defaults, ...$this->values];
    }

    #[Override]
    public function get(string $field, mixed $default = null): mixed
    {
        $all = $this->all();
        if (! array_key_exists($field, $all)) {
            throw UnknownSettingException::field(TrackingSection::NAME, $field);
        }

        return $all[$field] ?? $default;
    }

    #[Override]
    public function section(): string
    {
        return TrackingSection::NAME;
    }

    #[Override]
    public function set(array $values): ActionResult
    {
        $this->saved = [...$this->saved, ...$values];
        if (Outcome::Done === $this->outcome) {
            $this->values = [...$this->values, ...$values];
        }

        return new ActionResult($this->outcome, null, $this->message);
    }
}
