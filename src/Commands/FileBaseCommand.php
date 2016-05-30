<?php
/**
 * Created by PhpStorm.
 * User: yinx
 * Date: 29/05/2016
 * Time: 12:01.
 */
namespace Yinx\TreeLogger\Commands;

use Illuminate\Console\Command;

abstract class FileBaseCommand extends Command
{
    /**
     * Initializes the baseUrl property.
     *
     * @var string
     */
    protected $controllerBaseUrl = '';

    /**
     * amount of controllers affected.
     *
     * @var int
     */
    protected $controllerCount = 0;

    /**
     * Starts the command loop.
     *
     * @return mixed
     */
    abstract protected function start();

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->controllerBaseUrl = $this->laravel['path'].DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers';
        $this->start();
    }

    /**
     * Check is the current file is a directory based on file and optional $parentDir.
     *
     * @param $file
     * @param null $parentDir
     * @return bool
     */
    protected function isDir($file, $parentDir = null)
    {
        if ($parentDir == null) {
            return is_dir($this->controllerBaseUrl.DIRECTORY_SEPARATOR.$file);
        } else {
            return is_dir($this->controllerBaseUrl.DIRECTORY_SEPARATOR.$parentDir.DIRECTORY_SEPARATOR.$file);
        }
    }

    /**
     * Returns an array with all files/directories where directories are the keys.
     *
     * @param $dir
     * @return array
     */
    protected function dirToArray($dir)
    {
        $result = [];

        $cdir = scandir($dir);
        foreach ($cdir as $key => $value) {
            if (! in_array($value, ['.', '..'])) {
                if (is_dir($dir.DIRECTORY_SEPARATOR.$value)) {
                    $result[$value] = $this->dirToArray($dir.DIRECTORY_SEPARATOR.$value);
                } else {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }

    protected function writeToFile($path, $contents)
    {
        if (! file_put_contents($path, $contents)) {
            $this->error('Something went wrong writing to '.$path);
        }
    }

    /**
     * Checks the file for existing log-lines.
     *
     * @param $fileContents
     * @return bool
     */
    protected function checkForLogLines($fileContents)
    {
        return preg_match('/(use Log;|Log::)/', $fileContents, $output_array);
    }
}
