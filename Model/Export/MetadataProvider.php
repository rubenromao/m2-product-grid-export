<?php
declare(strict_types=1);

namespace RubenRomao\ProductGridExport\Model\Export;

use Exception;
use Magento\Catalog\Model\Product;
use Magento\Framework\Api\Search\Document;
use Magento\Framework\Api\Search\DocumentInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponentInterface;
use Magento\Ui\Api\BookmarkManagementInterface;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Ui\Model\Export\MetadataProvider as CoreClassMetadataProvider;

/**
 * Provides metadata for exporting product grid data.
 */
class MetadataProvider extends CoreClassMetadataProvider
{
    /**
     * MetadataProvider constructor.
     *
     * @param BookmarkManagementInterface $bookmarkManagement
     * @param Filter $filter
     * @param TimezoneInterface $localeDate
     * @param ResolverInterface $localeResolver
     * @param string $dateFormat
     * @param array $data
     */
    public function __construct(
        private readonly BookmarkManagementInterface $bookmarkManagement,
        Filter $filter,
        TimezoneInterface $localeDate,
        ResolverInterface $localeResolver,
        $dateFormat = 'M j, Y H:i:s A',
        array $data = [],
    ) {
        parent::__construct($filter, $localeDate, $localeResolver, $dateFormat, $data);
    }

    /**
     * Get columns.
     *
     * @param UiComponentInterface $component
     * @return UiComponentInterface[]
     * @throws Exception
     */
    public function getColumns(UiComponentInterface $component): array
    {
        if (!isset($this->columns[$component->getName()])) {
            $activeColumns = $this->getActiveColumns($component);

            $columns = $this->getColumnsComponent($component);
            $components = $columns->getChildComponents();

            foreach ($activeColumns as $columnName) {
                $column = $components[$columnName] ?? null;

                if (isset($column) && $column->getData('config/label')
                    && $column->getData('config/dataType') !== 'actions') {
                    $this->columns[$component->getName()][$column->getName()] = $column;
                }
            }
        }

        return $this->columns[$component->getName()];
    }

    /**
     * Get active columns.
     *
     * @param UiComponentInterface $component
     * @return array
     */
    public function getActiveColumns(UiComponentInterface $component): array
    {
        $bookmark = $this->bookmarkManagement->getByIdentifierNamespace('current', $component->getName());

        $config = $bookmark->getConfig();

        // Remove all invisible columns as well as ids, and actions columns.
        $columns = array_filter(
            $config['current']['columns'],
            fn($config, $key) => $config['visible'] && !in_array($key, ['ids', 'actions']),
            ARRAY_FILTER_USE_BOTH,
        );

        // Sort by position in the grid.
        uksort(
            $columns,
            fn($a, $b) => $config['current']['positions'][$a] <=> $config['current']['positions'][$b]
        );

        return array_keys($columns);
    }

    /**
     * Get columns component.
     *
     * @param Product|Document|DocumentInterface $document
     * @param array $fields
     * @param array $options
     * @return string[]
     */
    public function getRowData(Product|Document|DocumentInterface $document, $fields, $options): array
    {
        return array_values(array_map(fn($field) => $this->getColumnData($document, $field), $fields));
    }

    /**
     * Retrieves the column data for a given document and field.
     *
     * @param Product $document
     * @param string $field
     * @return array|string|null
     */
    public function getColumnData(Product $document, string $field): array|string|null
    {
        $value = $document->getData($field);
        if (is_array($value)) {
            return implode(', ', $value);
        }
        return $value;
    }
}
