<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Field;

use Contenir\Tracking\Provider;
use Override;

/**
 * A field holding the Google tag ID (G-, GT- or AW-): tracking:google-tag.
 *
 * @api
 */
final class GoogleTagField extends AbstractTagIdField
{
    public const string NAME = 'google-tag';

    #[Override]
    protected function provider(): Provider
    {
        return Provider::GoogleTag;
    }
}
