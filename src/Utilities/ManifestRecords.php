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

    public function __construct(iterable $items, string $manifestPath)
    {
        parent::__construct($items);
        $this->manifestPath = $manifestPath;
    }

    public function getManifestPath(): string
    {
        return $this->manifestPath;
    }
}
