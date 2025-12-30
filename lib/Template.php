<?php
namespace Ucg;

class Template
{
    private array $vars = [];
    public string $template_dir = __DIR__ . '/../templates/';

    public function assign(string $key, $value): void
    {
        $this->vars[$key] = $value;
    }

    public function display(string $tpl): void
    {
        echo $this->fetch($tpl);
    }

    public function fetch(string $tpl): string
    {
        $path = $this->template_dir . $tpl;
        if (!is_file($path)) {
            throw new \RuntimeException("Template not found: {$tpl}");
        }
        extract($this->vars);
        ob_start();
        include $path;
        return ob_get_clean();
    }
}
