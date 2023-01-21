<?php
declare(strict_types=1);

namespace ViteHelper\Utilities;

use Cake\Collection\Collection;

/**
 * A collection of ViteJS manifest entries
 *
 * @see ManifestRecord
 * @see ViteManifest
 */
final class ManifestRecords extends Collection
{
    private string $manifestPath;

    /**
     * @param iterable $items items for the collection
     * @param string $manifestPath path to the manifest file these records come from
     */
    public function __construct(iterable $items, string $manifestPath)
    {
        parent::__construct($items);
        $this->manifestPath = $manifestPath;
    }

    /**
     * @return string
     */
    public function getManifestPath(): string
    {
        return $this->manifestPath;
    }
}
