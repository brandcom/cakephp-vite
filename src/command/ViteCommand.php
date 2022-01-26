<?php
declare(strict_types=1);

namespace ViteHelper\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Strings;
use ViteHelper\Utilities\ViteManifest;

/**
 * Vite command.
 */
class ViteCommand extends Command
{
    private ViteManifest $manifest;

    public function initialize(): void
    {
        parent::initialize();
        $this->manifest = new ViteManifest();
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        switch ($args->getArgumentAt(0)) {
            case 'tidy':
                $this->tidy($io);
                break;
            default:
                $io->out($args->getArguments());
                break;
        }

        return null;
    }

    private function tidy(ConsoleIo $io): bool
    {
        $dir = $this->manifest->getBuildAssetsDir();
        $manifest_files = array_merge(
            $this->manifest->getJsFiles(false),
            $this->manifest->getCssFiles()
        );

        $build_files = Finder::findFiles('*.js', '*.css')->in($dir);

        $outdated_files = [];
        foreach ($build_files as $build_file) {

            /**
             * @var \SplFileInfo $build_file
             */
            if (!in_array(
                DS . Strings::after($build_file->getRealPath(), WWW_ROOT),
                $manifest_files
            )) {

                if (is_dir($build_file->getRealPath()) || !$build_file->getRealPath()) {
                    continue;
                }

                $outdated_files[] = $build_file->getRealPath();
            }
        }

        if (count($outdated_files) === 0) {
            $io->out("There are no outdated files. ");
            return true;
        }

        $confirmation = $io->ask("Do you want to delete " . count($outdated_files) . " files? (Y / N)");

        if ("Y" === strtoupper($confirmation)) {
            foreach ($outdated_files as $file) {
                try {
                    $io->out("Deleting " . Strings::after($file, ROOT));
                    FileSystem::delete($file);
                } catch (\Exception $e) {
                    $io->out("Could not delete file " . $file);
                }
            }

            $io->out("OK. ");
            return true;
        }

        $io->out("Nothing was deleted. ");
        return true;
    }
}
