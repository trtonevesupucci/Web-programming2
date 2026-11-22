<?php

class Logger
{
    public static function info($message)
    {
        self::write('info.log', 'INFO', $message);
    }

    public static function error($message)
    {
        self::write('error.log', 'ERROR', $message);
    }

    private static function write($file, $level, $message)
    {
        $dir = Config::LOG_DIR();
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $level . ' ' . $message . PHP_EOL;
        @file_put_contents($dir . '/' . $file, $line, FILE_APPEND | LOCK_EX);
    }
}
