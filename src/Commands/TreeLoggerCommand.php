<?php

namespace Yinx\TreeLogger\Commands;

use Illuminate\Console\Command;

class TreeLoggerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log:controller {--rm : Removes loglines instead.} {--v : Set the output level to verbose.} {blacklist?* : Define functions to blacklist.} {--construct : Override default behaviour and place loglines in construct functions.}';

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

    /**TODO implement blacklist and __construct whitelist.

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->controllerBaseUrl = $this->laravel['path'].DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers';

        if ($this->confirm('This command will CHANGE your controllers.'."\n".'Are you sure you want to continue? [y|N]')) {
            $filesArray = $this->dirToArray($this->controllerBaseUrl);
            $this->loopFiles($filesArray);
        }
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
                if (is_dir($this->controllerBaseUrl.DIRECTORY_SEPARATOR.$parentDir.DIRECTORY_SEPARATOR.$file)) {
                    $this->loopFiles($value, $parentDir.DIRECTORY_SEPARATOR.$file);
                } else {
                    if ($this->option('rm')) {
                        $this->removeAllLogsInControllers($parentDir.DIRECTORY_SEPARATOR.$value);
                    } else {
                        $this->writeToFile($parentDir.DIRECTORY_SEPARATOR.$value);
                    }
                }
            } else {
                if (is_dir($this->controllerBaseUrl.DIRECTORY_SEPARATOR.$file)) {
                    $this->loopFiles($value, key($array));
                } else {
                    if ($this->option('rm')) {
                        if ($this->confirm('Are you sure you want to remove ALL the log lines? [y|N]')) {
                            $this->removeAllLogsInControllers($value);
                        }
                    } else {
                        $this->writeToFile($value);
                    }
                }
            }
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
                $logReplace = 'Log::info(';

                $fileContents = preg_replace('/(public |protected |private )?function (.*\\))[\\r]?[\\n]?[\\t]*[ ]*{/U', '$0'."\n\t\t".$logReplace."'$2');", $fileContents);

                file_put_contents($filePath, $fileContents);
                if ($this->option('v')) {
                    $this->info('Wrote logline to '.$file);
                }
            }
        } else {
            $logReplace = 'Log::info(';

            $fileContents = preg_replace('/use .*;/', 'use Log;'."\n".'$0', $fileContents, 1);

            $fileContents = preg_replace('/(public |protected |private )?function (.*\\))[\\r]?[\\n]?[\\t]*[ ]*{/U', '$0'."\n\t\t".$logReplace."'$2');", $fileContents);

            file_put_contents($filePath, $fileContents);
            if ($this->option('v')) {
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
        if ($this->option('v')) {
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
