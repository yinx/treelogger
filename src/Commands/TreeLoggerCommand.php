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
    protected $signature = 'log:controller {--rm : Removes log-lines instead.} {--v : Set the output level to verbose.} {blacklist?* : Define controllers to blacklist.}';

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
     * The regex string which is used to find the function tags in each controller.
     *
     * @var string
     */
    protected $regexPattern = '/(public |protected |private )?'. // matches an optional method visibility
    'function (.*)'. // 'function' followed by the method name and possible type-hint.
    '([$].*)?\\)'. // optional parameters
    '[\\r]?[\\n]?[\\t]*[ ]*{'. // possible newlines/tabs/carriage returns and spaces followed by a '{'
    '/U'; // Inverts the greediness so they are not greedy by default.

    protected $controllerCount = 0;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->controllerBaseUrl = $this->laravel['path'].DIRECTORY_SEPARATOR.'Http'.DIRECTORY_SEPARATOR.'Controllers';

        if($this->argument('blacklist')){
            $this->error("The blacklist argument hasn't been implemented yet. The command will put logs in every controller.");
        }

        if ($this->option('rm')) {
            if ($this->confirm('This command will REMOVE ALL your log-lines from your controllers.'."\n".'Are you sure you want to continue? [y|N]')) {
                $this->start();
                $this->info("Removed log-lines in ". $this->controllerCount." controllers.");
            }
        }else {
            if ($this->confirm('This command will CHANGE your controllers.'."\n".'Are you sure you want to continue? [y|N]')) {
                $this->start();
                $this->info("Wrote log-lines to ". $this->controllerCount." controllers.");
            }
        }
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
                    if ($this->option('rm')) {
                        $this->removeAllLogsInControllers($parentDir.DIRECTORY_SEPARATOR.$value);
                    } else {
                        $this->getFileToWrite($parentDir.DIRECTORY_SEPARATOR.$value);
                    }
                }
            } else {
                if ($this->isDir($file)) {
                    $this->loopFiles($value, $file);
                } else {
                    if ($this->option('rm')) {
                        $this->removeAllLogsInControllers($value);
                    } else {
                        $this->getFileToWrite($value);
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
     * Callback method for the regex replace.
     * Checks if the function definition has multiple parameters and replaces accordingly
     *
     * @param $match
     * @return string
     */
    private function regexCallback($match){
        return empty($match[3]) ?
            $match[0]."\n\t\tLog::info('".$match[2].")');" :
            $match[0]."\n\t\tLog::info('".$match[2]."'. ".str_replace(',', ".','.", $match[3]).".')' );";
    }

    /**
     * Gets the file, checks for existing log-lines
     *
     * @param $file
     */
    public function getFileToWrite($file)
    {
        $filePath = $this->controllerBaseUrl.DIRECTORY_SEPARATOR.$file;
        $fileContents = file_get_contents($filePath);

        if ($this->checkForLogLines($fileContents)) {
            if ($this->confirm('We might have found some log-lines in the file '.$file.'.'."\n".' Do you want to continue? [y|N]')) {
                $this->writeToFile($file, $filePath, $fileContents);
            }
        } else {
            $fileContents = preg_replace('/use .*;/', 'use Log;'."\n".'$0', $fileContents, 1);
            $this->writeToFile($file, $filePath, $fileContents);
        }
        $this->controllerCount++;
    }

    /**
     * Write content to file based on the regex.
     *
     * @param $file
     * @param $filePath
     * @param $fileContents
     */
    private function writeToFile($file,$filePath,$fileContents){
        $fileContents = preg_replace_callback($this->regexPattern,
            [$this,'regexCallback'],
            $fileContents);
        file_put_contents($filePath, $fileContents);
        if ($this->option('v')) {
            $this->info('Wrote log-line to '.$file);
        }
    }

    /**
     * Checks the file for existing log-lines.
     *
     * @param $fileContents
     * @return bool
     */
    public function checkForLogLines($fileContents)
    {
        return preg_match('/(use Log;|Log::)/', $fileContents, $output_array);
    }

    /**
     * Removes the log lines in the $file.
     *
     * @param $file
     */
    public function removeAllLogsInControllers($file)
    {
        if ($this->option('v')) {
            $this->info('Removing log-line in '.$file);
        }
        $filePath = $this->controllerBaseUrl.DIRECTORY_SEPARATOR.$file;

        $fileContents = file_get_contents($filePath);
        $this->controllerCount++;

        $fileContents = preg_replace("/([\n| |\t]use Log;|[\n| |\t]*Log::.*\\(.*\\);)/", '', $fileContents);

        if (! file_put_contents($filePath, $fileContents)) {
            $this->error('Something went wrong writing to '.$file);
        }
    }
}
