<?php

class View
{
    public $template = null;
    public $viewfile = null;
    public $vars = [];
    public $sanitize;
    public $controller;

    public $header_file = null;
    public $footer_file = null;
    public $layout = null;

    public function __construct($sanitize = null, $controller = null)
    {
        $this->sanitize = $sanitize;
        $this->controller = $controller;
    }

    public function setFile($template, $viewfile = null)
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
        $content = $this->buildContent();

        if (!empty($this->layout)) {
            $this->renderLayout($content);
        } else {
            echo $content;
        }
    }

    private function buildContent()
    {
        ob_start();

        $s = $this->sanitize;
        $c = $this->controller;

        extract($this->vars, EXTR_SKIP);

        if (!empty($this->header_file)) {
            if (!file_exists($this->header_file)) {
                throw new Exception("Header file not found: {$this->header_file}");
            }
            include $this->header_file;
        }

        if (!empty($this->template)) {
            if (!file_exists($this->template)) {
                throw new Exception("Template not found: {$this->template}");
            }
            include $this->template;
        } elseif (!empty($this->viewfile)) {
            if (!file_exists($this->viewfile)) {
                throw new Exception("View file not found: {$this->viewfile}");
            }
            include $this->viewfile;
        } else {
            throw new Exception("No template or view file specified.");
        }

        if (!empty($this->footer_file)) {
            if (!file_exists($this->footer_file)) {
                throw new Exception("Footer file not found: {$this->footer_file}");
            }
            include $this->footer_file;
        }

        return ob_get_clean();
    }

    private function renderLayout($content)
    {
        if (!file_exists($this->layout)) {
            throw new Exception("Layout file not found: {$this->layout}");
        }

        $s = $this->sanitize;
        $c = $this->controller;

        extract($this->vars, EXTR_SKIP);

        $_poi_content = $content;

        include $this->layout;
    }
}
