<?php


namespace Xandros15\Tumbler;


use Exception;

final class Install
{
    public static function afterInstall()
    {
        $install = new static();
        $install->setConfig(__DIR__ . '/../sample.config.yaml');
        $install->setPermission([__DIR__ . '/../tumbler' => "0775", __DIR__ . '/../tumbler.bat' => "0775"]);
    }

    /**
     * @param array $paths
     */
    private function setPermission(array $paths): void
    {
        foreach ($paths as $path => $permission) {
            echo "chmod('$path', $permission)...";
            if (is_dir($path) || is_file($path)) {
                try {
                    if (fileperms($path) === octdec($permission) || chmod($path, octdec($permission))) {
                        echo "done.\n";
                    };
                } catch (Exception $e) {
                    echo $e->getMessage() . "\n";
                }
            } else {
                echo "file not found.\n";
            }
        }
    }

    /**
     * @param string $sampleFile
     */
    private function setConfig(string $sampleFile): void
    {
        $basename = basename($sampleFile);
        $dirname = dirname($sampleFile);
        $basename = str_replace('sample.', '', $basename);
        $copyName = $dirname . DIRECTORY_SEPARATOR . $basename;
        echo "making a config...";

        if (!is_file($copyName)) {
            if (is_file($sampleFile)) {
                copy($sampleFile, $copyName);
            } else {
                echo "sample file not found.\n";
            }
        }
        echo "done.\n";
    }
}
