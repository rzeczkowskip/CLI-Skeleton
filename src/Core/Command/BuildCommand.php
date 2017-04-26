<?php
namespace App\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Exception\NoSuchOptionException;
use Symfony\Component\OptionsResolver\Exception\OptionDefinitionException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BuildCommand extends Command
{
    const DIST_DIR = 'dist';

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var array
     */
    private $options;

    /**
     * @var string
     */
    private $rootPath;

    /**
     * @param Filesystem $fs
     * @param array $options
     * @param string $rootPath
     *
     * @throws UndefinedOptionsException If an option name is undefined
     * @throws InvalidOptionsException   If an option doesn't fulfill the
     *                                   specified validation rules
     * @throws MissingOptionsException   If a required option is missing
     * @throws OptionDefinitionException If there is a cyclic dependency between
     *                                   lazy options and/or normalizers
     * @throws NoSuchOptionException     If a lazy option reads an unavailable option
     * @throws AccessException           If called from a lazy option or normalizer
     */
    public function __construct(Filesystem $fs, array $options, $rootPath)
    {
        $this->fs = $fs;
        $this->options = $options;
        $this->rootPath = realpath($rootPath);

        parent::__construct();
    }

    public function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Build application .phar archive')
            ->addOption(
                'dist-dir',
                null,
                InputOption::VALUE_REQUIRED,
                'Dist output dir',
                $this->rootPath . '/' . self::DIST_DIR
            )
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $this->resolveOptions($this->options);

        $distPath = $input->getOption('dist-dir');
        $filePath = $distPath . '/' . $options['slug'] . '.phar';

        if (!$this->fs->exists($distPath)) {
            $this->fs->mkdir($distPath);
        } else {
            $this->fs->remove($filePath);
        }

        $phar = new \Phar($filePath);
        $phar->startBuffering();

        array_unshift($options['include'], sprintf('%s/(etc|src|vendor|composer.(json|lock))', $this->rootPath));
        $pharRegex = sprintf('/(%s)/',
            str_replace('/', '\\/', implode('|', $options['include']))
        );
        $phar->buildFromDirectory($this->rootPath, $pharRegex);

        $phar->setStub($this->getStub($options['slug']));

        $phar->compressFiles(\Phar::GZ);
        $phar->stopBuffering();

        $this->fs->chmod($filePath, 0755);

        $output->writeln('Phar archive saved in ' . $filePath);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    private function resolveOptions(array $options)
    {
        $resolver = new OptionsResolver();

        $resolver->setRequired(['slug']);
        $resolver->setDefault('include', []);

        return $resolver->resolve($options);
    }

    /**
     * @param string $appSlug
     *
     * @return string
     */
    private function getStub($appSlug)
    {
        $stub = <<<'EOF'
#!/usr/bin/env php
<?php
Phar::mapPhar('{{APP_SLUG}}.phar');

require 'phar://{{APP_SLUG}}.phar/src/bootstrap.php';

$container->get('app')->run();

__HALT_COMPILER();
EOF;

        return str_replace('{{APP_SLUG}}', $appSlug, $stub);
    }
}
