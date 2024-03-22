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
     * @param \ViteHelper\Enum\Environment $environment the environment where the script should be rendered in the
     *                                                  specified block
     * @param string|null $block the block name
     * @param mixed|null $plugin plugin name
     * @param array $elementOptions options for HTML element tag
     * @param bool $is_rendered shows if the item was already rendered or not
     */
    public function __construct(
        public readonly string $file,
        public readonly Environment $environment,
        public ?string $block = null,
        public mixed $plugin = null,
        public array $elementOptions = [],
        public bool $is_rendered = false,
    ) {
        parent::__construct(RecordType::SCRIPT);
    }
}
