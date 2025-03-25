<?php

namespace Simp\Core\modules\structures\views;

use Simp\Core\lib\installation\SystemDirectory;
use Symfony\Component\Yaml\Yaml;

class ViewsManager extends SystemDirectory
{
    protected array $views = [];
    protected string $view = '';
    public function __construct() {

        parent::__construct();
        $this->view = $this->setting_dir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'views';
        if (!is_dir($this->view)) {
            @mkdir($this->view);
        }
        $list = array_diff(scandir($this->view) ?? [], ['.', '..']);
        foreach ($list as $file) {
            $full_path = $this->view . DIRECTORY_SEPARATOR . $file;
            if (file_exists($full_path)) {
                $name = pathinfo($full_path, PATHINFO_FILENAME);
                $this->views[$name] = Yaml::parseFile($full_path);
            }
        }
    }

    public function getViews(): array
    {
        return $this->views;
    }

    public function getView(string $name): array
    {
        return $this->views[$name] ?? [];
    }

    public function addView(string $name, array $view): bool
    {
        $this->views[$name] = $view;
        $view_path = $this->view . DIRECTORY_SEPARATOR . $name . '.yml';
        return !empty(file_put_contents($view_path, Yaml::dump($view, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)));
    }

    public static function viewsManager(): ViewsManager
    {
        return new ViewsManager();
    }
}