<?php

class View
{
    public $template = null;
    public $viewfile = null;
    public $vars = [];
    public $sanitize;
    public $controller;

    public function __construct($sanitize = null, $controller = null)
    {
        $this->sanitize = $sanitize;
        $this->controller = $controller;
    }

    public function setFile($template, $viewfile)
    {
        $this->template = $template;
        $this->viewfile = $viewfile;
    }

    public function setVars($vars)
    {
        $this->vars = $vars;
    }

    public function display()
    {
        if ($this->template) {
            $this->renderTemplate();
        } else {
            $this->renderContent();
        }
    }

    private function renderContent()
    {
        if (!file_exists($this->viewfile)) {
            throw new Exception("View file not found: {$this->viewfile}");
        }

        $s = $this->sanitize;
        $c = $this->controller;

        extract($this->vars, EXTR_SKIP);
        include $this->viewfile;
    }

    private function renderTemplate()
    {
        if (!file_exists($this->template)) {
            throw new Exception("Template not found: {$this->template}");
        }

        $s = $this->sanitize;
        $c = $this->controller;

        extract($this->vars, EXTR_SKIP);
        include $this->template;
    }
}
