<?php
declare(strict_types=1);

namespace RubenRomao\ProductGridExport\Model\Export;

use Exception;
use Magento\Framework\Convert\ExcelFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Ui\Model\Export\ConvertToXml as CoreClassConvertToXml;
use Magento\Ui\Model\Export\MetadataProvider;
use Magento\Ui\Model\Export\SearchResultIteratorFactory;
use Psr\Log\LoggerInterface;
use RubenRomao\ProductGridExport\Api\LazySearchResultIteratorInterface;

/**
 * Create XLS file from grid data.
 */
class ConvertToXls extends CoreClassConvertToXml
{
    public const int DEFAULT_PAGE_SIZE = 200;
    public const string EXPORT_PATH = 'export/';
    public const string XSL_EXTENSION = '.xls';

    /**
     * ConvertToXls constructor.
     *
     * @param LazySearchResultIteratorInterface $collection
     * @param LoggerInterface $logger
     * @param Filesystem $filesystem
     * @param Filter $filter
     * @param \RubenRomao\ProductGridExport\Model\Export\MetadataProvider $metadataProvider
     * @param ExcelFactory $excelFactory
     * @param SearchResultIteratorFactory $iteratorFactory
     * @throws FileSystemException
     */
    public function __construct(
        private readonly LazySearchResultIteratorInterface $collection,
        private readonly LoggerInterface $logger,
        Filesystem $filesystem,
        Filter $filter,
        MetadataProvider $metadataProvider,
        ExcelFactory $excelFactory,
        SearchResultIteratorFactory $iteratorFactory,
    ) {
        parent::__construct($filesystem, $filter, $metadataProvider, $excelFactory, $iteratorFactory);
    }

    /**
     * Returns XLS file.
     *
     * @return array
     * @throws LocalizedException
     */
    public function generateXls(): array
    {
        $component = $this->filter->getComponent();

        $fileType = self::XSL_EXTENSION;
        $filePath = $this->createFilePath($component->getName());

        $this->createFile($filePath, $component, $fileType);

        return [
            'type' => 'filename',
            'value' => $filePath,
            'rm' => true // remove the file after use
        ];
    }

    /**
     * Returns XLS or XML file.
     *
     * @param string $filePath
     * @param mixed $component
     * @param string $fileType
     * @return void
     * @throws LocalizedException
     */
    public function createFile(string $filePath, mixed $component, string $fileType): void
    {
        try {
            $this->filter->applySelectionOnTargetProvider();
            $dataProvider = $component->getContext()->getDataProvider();
            $searchResult = $dataProvider->getSearchResult()
                ->setCurPage(1)
                ->setPageSize($this->pageSize ?? self::DEFAULT_PAGE_SIZE);
            $data = $this->excelFactory->create([
                'iterator' => $this->collection->getYieldItems($searchResult),
                'rowCallback' => [$this, 'getRowData'],
            ]);
            $this->directory->create(rtrim(self::EXPORT_PATH, '/'));
            $stream = $this->directory->openFile($filePath, 'w+');
            $stream->lock();
            $data->setDataHeader($this->metadataProvider->getHeaders($component));
            $data->write($stream, $component->getName() . $fileType);
            $stream->unlock();

        } catch (FileSystemException $e) {
            $this->logger->critical(__('Unable to create the export file'), $e->getTrace());
            throw new FileSystemException(__('Unable to create the export file'));

        } catch (LocalizedException|Exception $e) {
            $this->logger->critical(__('Something went wrong when creating the export file.'), $e->getTrace());
            throw new LocalizedException(__('Something went wrong when creating the export file.'));

        } finally {
            if (isset($stream)) {
                $stream->close();
            }
        }
    }

    /**
     * Create a file path.
     *
     * @param string $componentName
     * @return string
     */
    private function createFilePath(string $componentName): string
    {
        $fileHashName = sha1(uniqid(microtime(), true));
        return self::EXPORT_PATH . $componentName . $fileHashName . self::XSL_EXTENSION;
    }

    /**
     * Get row data for an item.
     *
     * @param mixed $document
     * @return array
     * @throws LocalizedException
     * @throws Exception
     */
    public function getRowData($document) : array
    {
        return $this->metadataProvider->getRowData(
            $document,
            $this->metadataProvider->getFields($this->filter->getComponent()),
            []
        );
    }
}
