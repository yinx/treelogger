<?php

namespace Yinx\TreeLogger\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class TreeLoggerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'log:controller';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Place a log-line in every function of every controller.';

    /**
     * Initializes the baseUrl property.
     *
     * @var string
     */
    protected $controllerBaseUrl = '';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->controllerBaseUrl = $this->laravel['path'].DIRECTORY_SEPARATOR.'Controllers';

        if ($this->option('remove')) {
            if ($this->confirm('This command will REMOVE ALL your loglines from your controllers.'."\n".'Are you sure you want to continue? [y|N]')) {
                $this->start();
            }
        } else {
            if ($this->confirm('This command will CHANGE your controllers.'."\n".'Are you sure you want to continue? [y|N]')) {
                $this->start();
            }
        }
    }

    public function getOptions()
    {
        return [
            ['remove', 'rm', InputOption::VALUE_NONE, 'Removes loglines instead.'],
        ];
    }

    /**
     * Starts the command loop.
     */
    public function start()
    {
        $filesArray = $this->dirToArray($this->controllerBaseUrl);
        $this->loopFiles($filesArray);
    }

    /**
     * Loops through the files and directories ($array) in de Controller folder.
     * recursive call if dir, if file calls remove or write.
     *
     * @param $array
     * @param null $parentDir parameter for recursive calls
     */
    public function loopFiles($array, $parentDir = null)
    {
        foreach ($array as $file => $value) {
            if ($parentDir != null) {
                if ($this->isDir($file, $parentDir)) {
                    $this->loopFiles($value, $parentDir.DIRECTORY_SEPARATOR.$file);
                } else {
                    if ($this->option('remove')) {
                        $this->removeAllLogsInControllers($parentDir.DIRECTORY_SEPARATOR.$value);
                    } else {
                        $this->writeToFile($parentDir.DIRECTORY_SEPARATOR.$value);
                    }
                }
            } else {
                if ($this->isDir($file)) {
                    $this->loopFiles($value, $file);
                } else {
                    if ($this->option('remove')) {
                        $this->removeAllLogsInControllers($value);
                    } else {
                        $this->writeToFile($value);
                    }
                }
            }
        }
    }

    /**
     * Check is the current file is a directory based on file and optional $parentDir.
     *
     * @param $file
     * @param null $parentDir
     * @return bool
     */
    public function isDir($file, $parentDir = null)
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
    public function dirToArray($dir)
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

    /**
     * Writes the log lines to the $file.
     *
     * @param $file
     */
    public function writeToFile($file)
    {
        $filePath = $this->controllerBaseUrl.DIRECTORY_SEPARATOR.$file;

        $fileContents = file_get_contents($filePath);

        if ($this->checkForLogLines($fileContents)) {
            if ($this->confirm('We might have found some log lines in the file '.$file.'.'."\n".' Do you want to continue? [y|N]')) {
                //TODO document regex
                $fileContents = preg_replace_callback('/(public |protected |private )?function (.*)([$].*)?\\)[\\r]?[\\n]?[\\t]*[ ]*{/U',
                    function ($match) {return empty($match[3]) ? $match[0]."\n\t\tLog::info('".$match[2].")');" : $match[0]."\n\t\tLog::info('".$match[2]."'. ".str_replace(',', ".','.", $match[3]).".')' );"; }, $fileContents);

                file_put_contents($filePath, $fileContents);
                if ($this->option('verbose')) {
                    $this->info('Wrote logline to '.$file);
                }
            }
        } else {
            //TODO document regex
            $fileContents = preg_replace('/use .*;/', 'use Log;'."\n".'$0', $fileContents, 1);
            $fileContents = preg_replace_callback('/(public |protected |private )?function (.*)([$].*)?\\)[\\r]?[\\n]?[\\t]*[ ]*{/U',
                function ($match) {return empty($match[3]) ? $match[0]."\n\t\tLog::info('".$match[2].")');" : $match[0]."\n\t\tLog::info('".$match[2]."'. ".str_replace(',', ".','.", $match[3]).".')' );"; }, $fileContents);
            file_put_contents($filePath, $fileContents);
            if ($this->option('verbose')) {
                $this->info('Writing logline to '.$file);
            }
        }
    }

    /**
     * Checks the file for existing loglines.
     *
     * @param $fileContents
     * @return bool
     */
    public function checkForLogLines($fileContents)
    {
        return preg_match('/(use Log;|Log::emergency\\(|Log::alert\\(|Log::critical\\(|Log::error\\(|Log::warning\\(|Log::notice\\(|Log::info\\(|Log::debug\\()/', $fileContents, $output_array);
    }

    /**
     * Removes the log lines in the $file.
     *
     * @param $file
     */
    public function removeAllLogsInControllers($file)
    {
        if ($this->option('verbose')) {
            $this->info('Removing logline in '.$file);
        }
        $filePath = $this->controllerBaseUrl.DIRECTORY_SEPARATOR.$file;

        $fileContents = file_get_contents($filePath);

        $fileContents = preg_replace("/([\n| |\t]use Log;|[\n| |\t]*Log::.*\\(.*\\);)/", '', $fileContents);

        if (! file_put_contents($filePath, $fileContents)) {
            $this->error('Something went wrong writing to '.$file);
        }
    }
}
