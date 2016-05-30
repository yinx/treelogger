<?php
/**
 * Created by PhpStorm.
 * User: yinx
 * Date: 30/05/2016
 * Time: 16:49
 */

namespace Yinx\TreeLogger\Commands;


class RemoveLogsCommand extends FileBaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'controller:log:remove {--v : Set the output level to verbose.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Removes all log-lines in every function of every controller.';


    /**
     * Starts the command loop.
     */
    public function start()
    {
        if ($this->confirm('This command will REMOVE ALL your log-lines from your controllers.'."\n".'Are you sure you want to continue? [y|N]')) {
            $filesArray = $this->dirToArray($this->controllerBaseUrl);
            $this->loopFiles($filesArray);
            $this->info("Removed log-lines in ". $this->controllerCount." controllers.");
        }

    }

    /**
     * Loops through the files and directories ($array) in de Controller folder.
     * recursive call if dir, if file calls remove.
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
                    $this->removeAllLogsInControllers($parentDir.DIRECTORY_SEPARATOR.$value);
                }
            } else {
                if ($this->isDir($file)) {
                    $this->loopFiles($value, $file);
                } else {
                    $this->removeAllLogsInControllers($value);
                }
            }
        }
    }

    /**
     * Removes the log lines in the $file.
     *
     * @param $file
     */
    protected function removeAllLogsInControllers($file)
    {
        if ($this->option('v')) {
            $this->info('Removing log-line in '.$file);
        }
        $filePath = $this->controllerBaseUrl.DIRECTORY_SEPARATOR.$file;

        $fileContents = file_get_contents($filePath);
        $this->controllerCount++;

        $fileContents = preg_replace("/([\n| |\t]use Log;|[\n| |\t]*Log::.*\\(.*\\);)/", '', $fileContents);

        $this->writeToFile($filePath,$fileContents);
    }
}