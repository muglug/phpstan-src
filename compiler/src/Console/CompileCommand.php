<?php declare(strict_types = 1);

namespace PHPStan\Compiler\Console;

use PHPStan\Compiler\Filesystem\Filesystem;
use PHPStan\Compiler\Process\ProcessFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CompileCommand extends Command
{

	/** @var Filesystem */
	private $filesystem;

	/** @var ProcessFactory */
	private $processFactory;

	/** @var string */
	private $dataDir;

	/** @var string */
	private $buildDir;

	public function __construct(
		Filesystem $filesystem,
		ProcessFactory $processFactory,
		string $dataDir,
		string $buildDir
	)
	{
		parent::__construct();
		$this->filesystem = $filesystem;
		$this->processFactory = $processFactory;
		$this->dataDir = $dataDir;
		$this->buildDir = $buildDir;
	}

	protected function configure(): void
	{
		$this->setName('phpstan:compile')
			->setDescription('Compile PHAR');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$this->processFactory->setOutput($output);

		$this->processFactory->create(['composer', 'require', '--no-update', 'dg/composer-cleaner:^2.0'], $this->buildDir);
		$this->fixComposerJson($this->buildDir);
		$this->processFactory->create(['composer', 'update', '--no-dev', '--classmap-authoritative'], $this->buildDir);

		$this->processFactory->create(['php', 'box.phar', 'compile', '--no-parallel'], $this->dataDir);

		return 0;
	}

	private function fixComposerJson(string $buildDir): void
	{
		$json = json_decode($this->filesystem->read($buildDir . '/composer.json'), true);

		// remove dev dependencies (they create conflicts)
		unset($json['require-dev'], $json['autoload-dev']);

		unset($json['replace']);
		$json['name'] = 'phpstan/phpstan';

		// simplify autoload (remove not packed build directory]
		$json['autoload']['psr-4']['PHPStan\\'] = 'src/';

		$encodedJson = json_encode($json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		if ($encodedJson === false) {
			throw new \Exception('json_encode() was not successful.');
		}

		$this->filesystem->write($buildDir . '/composer.json', $encodedJson);
	}

}
