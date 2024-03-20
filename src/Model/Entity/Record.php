<?php
declare(strict_types=1);

namespace ViteHelper\Model\Entity;

use ViteHelper\Enum\RecordType;

abstract class Record
{
	protected RecordType $type;

	public function __construct(RecordType $type) {
		$this->type = $type;
	}

	/**
	 * returns the record type
	 *
	 * @return \ViteHelper\Enum\RecordType
	 */
	public function getType(): RecordType
	{
		return $this->type;
	}
}
