<?php declare(strict_types=1);

namespace RubenRomao\ProductGridExport\Ui\Component;

use Magento\Ui\Component\ExportButton as CoreClassExportButton;

/**
 * Remove the XML export option from the export list.
 */
class ExportButton extends CoreClassExportButton
{
    /**
     * Prepare component configuration to remove the XML export option.
     *
     * @return void
     */
    public function prepare(): void
    {
        $config = $this->getData('config');
        $options = $config['options'];
        if (!array_key_exists('xml', $options)) {
            parent::prepare();

            return;
        }
        unset($options['xml']);
        $config['options'] = $options;
        $this->setData('config', $config);
        parent::prepare();
    }
}
