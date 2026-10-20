<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Field;

use Contenir\Extension\Api\Context\ExtensionContextInterface;
use Contenir\Extension\Api\Field\FieldTypeInterface;
use Contenir\Extension\Api\Form\FieldType;
use Contenir\Tracking\Consent\Regions;
use Override;

use function implode;
use function is_string;

/**
 * A field holding the regions where Google's tags wait for consent:
 * tracking:regions. Codes are checked as they are saved and stored upper
 * case, separated by commas.
 *
 * @api
 */
final class RegionsField implements FieldTypeInterface
{
    public const string NAME = 'regions';

    private static function text(mixed $value): string
    {
        return is_string($value) ? $value : '';
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
        $text = self::text($value);
        if ([] !== Regions::problems($text)) {
            return $text;
        }

        return implode(', ', Regions::parse($text)->codes);
    }

    #[Override]
    public function options(ExtensionContextInterface $context): array
    {
        return [];
    }

    #[Override]
    public function validate(mixed $value): array
    {
        return Regions::problems(self::text($value));
    }
}
