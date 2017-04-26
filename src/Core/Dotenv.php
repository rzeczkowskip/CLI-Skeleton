<?php
namespace App\Core;

use Symfony\Component\Dotenv\Exception\FormatException;
use Symfony\Component\Filesystem\Filesystem;

class Dotenv
{
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var \Symfony\Component\Dotenv\Dotenv
     */
    private $dotenv;

    /**
     * @var array
     */
    private $options;

    /**
     * @param Filesystem $fs
     * @param \Symfony\Component\Dotenv\Dotenv $dotenv
     * @param array $options
     */
    public function __construct(Filesystem $fs, \Symfony\Component\Dotenv\Dotenv $dotenv, array $options = [])
    {
        $this->fs = $fs;
        $this->dotenv = $dotenv;
        $this->options = $options;
    }

    /**
     * @throws FormatException
     * @throws \RuntimeException
     */
    public function load()
    {
        if (array_key_exists('file', $this->options) && $this->options['file'] !== null) {
            $this->dotenv->load(getcwd() . '/' . $this->options['file']);
        } else if ($this->fs->exists(getcwd() . '/.env')) {
            $this->dotenv->load('.env');
        }

        if (array_key_exists('required', $this->options) && is_array($this->options['required'])) {
            $missing = [];
            foreach ($this->options['required'] as $required) {
                if (getenv($required) === false) {
                    $missing[] = $required;
                }
            }

            if ($missing) {
                throw new \RuntimeException(sprintf(
                    'Some of the env vars are missing (%s)',
                    implode(', ', $missing)
                ));
            }
        }
    }
}
