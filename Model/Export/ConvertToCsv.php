<?php
declare(strict_types=1);

namespace RubenRomao\ProductGridExport\Model\Export;

use Exception;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Ui\Model\Export\ConvertToCsv as CoreClassConvertToCsv;
use Psr\Log\LoggerInterface;
use RubenRomao\ProductGridExport\Api\LazySearchResultIteratorInterface;

/**
 * Create CSV file from grid data.
 */
class ConvertToCsv extends CoreClassConvertToCsv
{
    public const int DEFAULT_PAGE_SIZE = 200;
    public const string EXPORT_PATH = 'export/';
    public const string EXTENSION = '.xml';

    /**
     * ConvertToCsv constructor.
     *
     * @param LazySearchResultIteratorInterface $collection
     * @param LoggerInterface $logger
     * @param Filesystem $filesystem
     * @param Filter $filter
     * @param MetadataProvider $metadataProvider
     * @throws FileSystemException
     */
    public function __construct(
        private readonly LazySearchResultIteratorInterface $collection,
        private readonly LoggerInterface $logger,
        Filesystem $filesystem,
        Filter $filter,
        MetadataProvider $metadataProvider,
    ) {
        parent::__construct($filesystem, $filter, $metadataProvider);
    }

    /**
     * Returns CSV file.
     *
     * @return array
     * @throws LocalizedException
     * @throws Exception
     */
    public function generateCsvFile(): array
    {
        $component = $this->filter->getComponent();
        $dataProvider = $component->getContext()->getDataProvider();
        $fields = $this->metadataProvider->getFields($component);

        $filePath = $this->createFilePath($component->getName());
        $this->createCsvFile($filePath, $fields, $dataProvider, $component);

        return [
            'type' => 'filename',
            'value' => $filePath,
            'rm' => true // remove the file after use
        ];
    }

    /**
     * Create a file path.
     *
     * @param string $componentName
     * @return string
     */
    public function createFilePath(string $componentName): string
    {
        $fileHashName = sha1(uniqid(microtime(), true));
        return self::EXPORT_PATH . $componentName . $fileHashName . self::EXTENSION;
    }

    /**
     * Create a CSV file.
     *
     * @param string $filePath
     * @param array $fields
     * @param mixed $dataProvider
     * @param mixed $component
     * @return void
     * @throws LocalizedException
     * @throws Exception
     */
    public function createCsvFile(
        string $filePath,
        array $fields,
        mixed $dataProvider,
        mixed $component
    ): void {

        try {
            $this->filter->applySelectionOnTargetProvider();
            $this->directory->create(rtrim(self::EXPORT_PATH, '/'));
            $stream = $this->directory->openFile($filePath, 'w+');
            $stream->lock();
            $stream->writeCsv($this->metadataProvider->getHeaders($component));
            $searchResult = $dataProvider->getSearchResult();
            $searchResult->setCurPage(1)->setPageSize(self::DEFAULT_PAGE_SIZE);
            $items = $this->collection->getYieldItems($searchResult);
            foreach ($items as $item) {
                $this->metadataProvider->convertDate($item, $component->getName());
                $stream->writeCsv($this->metadataProvider->getRowData($item, $fields, []));
            }
            $stream->unlock();

        } catch (FileSystemException $e) {
            $this->logger->critical(__('ERROR: Unable to create the export file'), $e->getTrace());
            throw new FileSystemException(__('ERROR: Unable to create the export file'));

        } catch (LocalizedException $e) {
            $this->logger->critical(__('Something went wrong when creating the export file.'), $e->getTrace());
            throw new LocalizedException(__('Something went wrong when creating the export file.'));

        } finally {
            if (isset($stream)) {
                $stream->close();
            }
        }
    }
}
