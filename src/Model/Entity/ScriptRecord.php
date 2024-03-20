<?php
declare(strict_types=1);

namespace ViteHelper\Model\Entity;

use ViteHelper\Enum\Environment;
use ViteHelper\Enum\RecordType;

class ScriptRecord extends Record
{
	public function __construct(
		public readonly string $file,
		public readonly Environment $environment,
		public string|null $block = null,
		public mixed $plugin = null,
		public array $elementOptions = [],
		public bool $is_rendered = false,
	) {
		parent::__construct(RecordType::SCRIPT);
	}
}
