<?php
declare(strict_types=1);

namespace ViteHelper\Model\Entity;

use ViteHelper\Enum\Environment;
use ViteHelper\Enum\RecordType;

class ScriptRecord extends Record
{
	/**
	 * Default constructor
	 * All data about a script file (.js/.ts)
	 *
	 * @param string $file the filepath
	 * @param array<\ViteHelper\Enum\Environment> $environment in which environment should we render this file
	 * @param string|null $block the block name
	 * @param string|null $plugin Plugin's name
	 * @param array $elementOptions options for HTML element tag
	 * @param bool $is_rendered shows if the item was already rendered or not
	 */
    public function __construct(
		string $file,
		array $environment = [Environment::PRODUCTION, Environment::DEVELOPMENT],
		readonly public ?string $block = null,
		readonly public ?string $plugin = null,
		public array $elementOptions = [],
		public bool $is_rendered = false,
    ) {
		parent::__construct(RecordType::STYLE, $file, $environment);
    }
}
