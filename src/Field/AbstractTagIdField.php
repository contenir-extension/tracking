<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Field;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Field\FieldTypeInterface;
use Contenir\Extension\Api\Form\FieldType;
use Contenir\Tracking\Provider;
use Override;

use function is_int;
use function is_string;

/**
 * A text field holding one provider's tag ID, checked as it is saved and
 * stored the way the provider writes it.
 *
 * @api
 */
abstract class AbstractTagIdField implements FieldTypeInterface
{
    private static function text(mixed $value): string
    {
        return is_string($value) || is_int($value) ? (string) $value : '';
    }

    #[Override]
    public function component(): ?string
    {
        return null;
    }

    #[Override]
    public function control(): FieldType
    {
        return FieldType::Text;
    }

    #[Override]
    public function normalise(mixed $value): mixed
    {
        return $this->provider()->normalise(self::text($value));
    }

    #[Override]
    public function options(ExtensionContextInterface $context): array
    {
        return [];
    }

    #[Override]
    public function validate(mixed $value): array
    {
        $problem = $this->provider()->problem(self::text($value));

        return null === $problem ? [] : [$problem];
    }

    /**
     * The provider whose IDs the field holds.
     */
    abstract protected function provider(): Provider;
}
