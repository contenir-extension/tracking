<?php

declare(strict_types=1);

namespace Contenir\Extension\Tracking\Field;

use Contenir\Tracking\Provider;
use Override;

/**
 * A field holding the LinkedIn Insight partner ID: tracking:linkedin-insight.
 *
 * @api
 */
final class LinkedInInsightField extends AbstractTagIdField
{
    public const string NAME = 'linkedin-insight';

    #[Override]
    protected function provider(): Provider
    {
        return Provider::LinkedInInsight;
    }
}
