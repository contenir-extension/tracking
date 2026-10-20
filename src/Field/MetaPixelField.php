<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Field;

use Contenir\Tracking\Provider;
use Override;

/**
 * A field holding the Meta pixel ID: tracking:meta-pixel.
 *
 * @api
 */
final class MetaPixelField extends AbstractTagIdField
{
    public const string NAME = 'meta-pixel';

    #[Override]
    protected function provider(): Provider
    {
        return Provider::MetaPixel;
    }
}
