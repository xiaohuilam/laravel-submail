<?php
namespace Bingoo\Mail;

class SubMailTemplate
{

    private $project;

    public function __construct($project, array $vars, array $links)
    {
        $this->project = $project;
        $this->vars = $vars;
        $this->links = $links;
    }

    private $vars;
    private $links;


    public function getProject()
    {
        return $this->project;
    }

    public function getVars()
    {
        $data = [];
        foreach ($this->vars as $key => $value) {
            $data[$key] = $value;
        }
        return $data;
    }

    public function getLinks()
    {
        $data = [];
        foreach ($this->links as $key => $value) {
            $data[$key] = $value;
        }
        return $data;
    }
}
