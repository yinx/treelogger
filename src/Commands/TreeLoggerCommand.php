<?php

namespace Yinx\TreeLogger\Commands;

class TreeLoggerCommand extends FileBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'controller:log:add {--v : Set the output level to verbose.} {blacklist?* : Define controllers to blacklist.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Place a log-line in every function of every controller.';

    /**
     * The regex string which is used to find the function tags in each controller.
     *
     * @var string
     */
    protected $regexPattern = '/(public |protected |private )?'.// matches an optional method visibility
    'function (.*)'.// 'function' followed by the method name and possible type-hint.
    '([$].*)?\\)'.// optional parameters
    '[\\r]?[\\n]?[\\t]*[ ]*{'.// possible newlines/tabs/carriage returns and spaces followed by a '{'
    '/U'; // Inverts the greediness so they are not greedy by default.

    /**
     * Starts the command loop.
     */
    public function start()
    {
        if ($this->argument('blacklist')) {
            $this->error("The blacklist argument hasn't been implemented yet. The command will put logs in every controller.");
        }

        if ($this->confirm('This command will CHANGE your controllers.'."\n".'Are you sure you want to continue? [y|N]')) {
            $filesArray = $this->dirToArray($this->controllerBaseUrl);
            $this->loopFiles($filesArray);
            $this->info('Wrote log-lines to '.$this->controllerCount.' controllers.');
        }
    }

    /**
     * Loops through the files and directories ($array) in de Controller folder.
     * recursive call if dir, if file calls write.
     *
     * @param $array
     * @param null $parentDir parameter for recursive calls
     */
    protected function loopFiles($array, $parentDir = null)
    {
        foreach ($array as $file => $value) {
            if ($parentDir != null) {
                if ($this->isDir($file, $parentDir)) {
                    $this->loopFiles($value, $parentDir.DIRECTORY_SEPARATOR.$file);
                } else {
                    $this->getFileToWrite($parentDir.DIRECTORY_SEPARATOR.$value);
                }
            } else {
                if ($this->isDir($file)) {
                    $this->loopFiles($value, $file);
                } else {
                    $this->getFileToWrite($value);
                }
            }
        }
    }

    /**
     * Gets the file, checks for existing log-lines.
     *
     * @param $file
     */
    protected function getFileToWrite($file)
    {
        $filePath = $this->controllerBaseUrl.DIRECTORY_SEPARATOR.$file;
        $fileContents = file_get_contents($filePath);

        if ($this->checkForLogLines($fileContents)) {
            if ($this->confirm('We might have found some log-lines in the file '.$file.'.'."\n".' Do you want to continue? [y|N]')) {
                $this->regexCheck($file, $filePath, $fileContents);
            }
        } else {
            $fileContents = preg_replace('/use .*;/', 'use Log;'."\n".'$0', $fileContents, 1);
            $this->regexCheck($file, $filePath, $fileContents);
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
    protected function regexCheck($file, $filePath, $fileContents)
    {
        $fileContents = preg_replace_callback($this->regexPattern,
            [$this, 'regexCallback'],
            $fileContents);
        $this->writeToFile($filePath, $fileContents);
        if ($this->option('v')) {
            $this->info('Wrote log-line to '.$file);
        }
    }

    /**
     * Callback method for the regex replace.
     * Checks if the function definition has multiple parameters and replaces accordingly.
     *
     * @param $match
     * @return string
     */
    protected function regexCallback($match)
    {
        return empty($match[3]) ?
            $match[0]."\n\t\tLog::info('".$match[2].")');" :
            $match[0]."\n\t\tLog::info('".$match[2]."'. ".str_replace(',', ".','.", $match[3]).".')' );";
    }
}
