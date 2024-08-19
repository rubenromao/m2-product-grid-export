<?php
declare(strict_types=1);

namespace RubenRomao\ProductGridExport\Api;

use Generator;
use Magento\Catalog\Ui\DataProvider\Product\ProductCollection;

/**
 * Interface LazySearchResultIteratorInterface for fetching items from the collection.
 */
interface LazySearchResultIteratorInterface
{
    /**
     * Fetch items from the collection.
     *
     * @param ProductCollection $collection
     * @return Generator
     */
    public function getYieldItems(ProductCollection $collection): Generator;
}
