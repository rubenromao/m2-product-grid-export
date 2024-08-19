<?php
declare(strict_types=1);

namespace RubenRomao\ProductGridExport\Controller\Adminhtml\Export;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use RubenRomao\ProductGridExport\Model\Export\ConvertToCsv;

/**
 * Generate CSV from grid data.
 */
class GridToCsv extends Action
{
    public const string EXPORT_FILENAME = 'export.csv';
    public const string BASE_DIR = 'var';

    /**
     * GridToCsv constructor.
     *
     * @param ConvertToCsv $converter
     * @param FileFactory $fileFactory
     * @param Context $context
     */
    public function __construct(
        private readonly ConvertToCsv $converter,
        private readonly FileFactory $fileFactory,
        Context $context,
    ) {
        parent::__construct($context);
    }

    /**
     * Export data provider to CSV
     *
     * @throws Exception
     * @throws LocalizedException
     */
    public function execute(): ResultInterface|ResponseInterface
    {
        return $this->fileFactory->create(
            self::EXPORT_FILENAME,
            $this->converter->generateCsvFile(),
            self::BASE_DIR
        );
    }
}
