<?php
/**
 * Created by PhpStorm.
 * User: crazytster
 * Date: 27.07.17
 * Time: 12:57
 */
include_once "php/Utils.php";

class Page
{
    public $page;
    public $View;
    public function init()
    {
        $this->page = Vars::get('page', null);
        $this->View = new Blitz('static/new_gui.tpl');
    }

    public function run()
    {
        switch ($this->page) {
            default: case "main_stat":
                $this->View->block('/STATIC', array('is_in' => '', 'is_checked' => '', 'is_main_selected'=> 'selected'));
                $this->View->block('/STATIC/MAIN_STAT');
                break;
            case "water":
                $this->View->block('/STATIC', array('is_in' => 'in', 'is_checked' => 'checked="checked"', 'page'=> $this->page));
                //$this->View->block('/STATIC', array('is_in' => 'in', 'is_checked' => 'checked="checked"', 'is_water_selected'=> 'selected'));
                $this->View->block('/STATIC/WATER');
                break;
            case "electricity":
                $this->View->block('/STATIC', array('is_in' => 'in', 'is_checked' => 'checked="checked"', 'is_electricity_selected'=> 'selected'));
                //$this->View->block('/STATIC/WATER');
                break;

        }
        $this->View->display();
    }
}

$page = new Page();
$page->init();
$page->run();