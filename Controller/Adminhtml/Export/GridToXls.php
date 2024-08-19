<?php
declare(strict_types=1);

namespace RubenRomao\ProductGridExport\Controller\Adminhtml\Export;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use RubenRomao\ProductGridExport\Model\Export\ConvertToXls;

/**
 * Generate XLS from grid data.
 */
class GridToXls extends Action
{
    public const string EXPORT_XLS_FILENAME = 'export.xls';
    public const string BASE_DIR = 'var';

    /**
     * GridToXls constructor.
     *
     * @param ConvertToXls $converter
     * @param FileFactory $fileFactory
     * @param Context $context
     */
    public function __construct(
        private readonly ConvertToXls $converter,
        private readonly FileFactory $fileFactory,
        Context $context,
    ) {
        parent::__construct($context);
    }

    /**
     * Export data provider to XLS.
     *
     * @return ResponseInterface
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute(): ResponseInterface
    {
        return $this->fileFactory->create(
            self::EXPORT_XLS_FILENAME,
            $this->converter->generateXls(),
            self::BASE_DIR
        );
    }
}
