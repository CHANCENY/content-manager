<?php

namespace Simp\Core\modules\assets_manager;

class AssetsManager
{
    public function getAssetsFile(string $filename, bool $content = true): string
    {
        $files = array_diff(scandir(__DIR__.DIRECTORY_SEPARATOR.'assets') ?? [], ['..', '.']);
        foreach ($files as $file) {
            $full_path = __DIR__.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.$file;
            if (file_exists($full_path) && $file === $filename) {
                return $content ? file_get_contents($full_path) : $full_path;
            }
            elseif (is_dir($full_path)) {
                $found = $this->recursive_read($full_path, $file ,$filename, $content);
                if ($found) {
                    return $found;
                }
            }
        }
        return '';
    }

    private function recursive_read(string $path, string $dir, string $filename, bool $content): string
    {
        $files = array_diff(scandir($path) ?? [], ['..', '.']);
        foreach ($files as $file) {
            $full_path = __DIR__.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.$file;

            if (file_exists($full_path) && $file === $filename) {
                return $content ? file_get_contents($full_path) : $full_path;
            }

            elseif (is_dir($full_path)) {
                $found = $this->recursive_read($full_path, $dir, $filename, $content);
                if (!empty($found)) {
                    return $found;
                }
            }
        }
        return '';
    }

}