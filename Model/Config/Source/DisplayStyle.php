<?php
declare(strict_types=1);

namespace Panth\PriceDropAlert\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DisplayStyle implements OptionSourceInterface
{
    public const COMPACT = 'compact';
    public const INLINE = 'inline';

    public function toOptionArray(): array
    {
        return [
            ['value' => self::COMPACT, 'label' => __('Compact link that opens a dialog (recommended)')],
            ['value' => self::INLINE, 'label' => __('Full form shown on the page')],
        ];
    }
}
