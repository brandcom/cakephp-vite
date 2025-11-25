<?php
declare(strict_types=1);

namespace ViteHelper\Model\Entity;

use ViteHelper\Enum\Environment;
use ViteHelper\Enum\RecordType;

abstract class Record
{
	/**
	 * Default constructor
	 *
	 * @param \ViteHelper\Enum\RecordType $type
	 * @param string $file the filepath
	 * @param array<\ViteHelper\Enum\Environment> $environment in which environment should we render this file
	 */
    public function __construct(
		readonly protected RecordType $type,
		readonly public string $file,
		readonly public array $environment = [Environment::PRODUCTION, Environment::DEVELOPMENT],
	) {}

    /**
     * returns the record type
     *
     * @return \ViteHelper\Enum\RecordType
     */
    public function getType(): RecordType
    {
        return $this->type;
    }

	/**
	 * Returns true if the given environment is in the allowed environments list
	 *
	 * @param \ViteHelper\Enum\Environment $environment
	 * @return bool
	 */
	public function isValidEnvironment(Environment $environment): bool
	{
		return in_array($environment, $this->environment, true);
	}
}
