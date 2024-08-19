<?php
declare(strict_types=1);

namespace RubenRomao\ProductGridExport\Model;

use Generator;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use RubenRomao\ProductGridExport\Api\LazySearchResultIteratorInterface;

/**
 * Iterator for lazy loading search results.
 */
class LazySearchResultIterator implements LazySearchResultIteratorInterface
{
    /**
     * Fetch items from the collection.
     *
     * @param ProductCollection $collection
     * @return Generator
     */
    public function getYieldItems(ProductCollection $collection): Generator
    {
        $lastPage = $collection->getLastPageNumber();
        for ($page = $collection->getCurPage(); $page <= $lastPage; $page++) {
            $items = $collection->setCurPage($page)->clear()->getItems();
            yield from $items;
        }
    }
}
