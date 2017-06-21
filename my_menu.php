<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class My_menu extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        $language = MY_Controller::getCurrentLanguage();
        $this->load->model('menu_model');
        $this->menu_model->setLanguage($language);
    }

    public function _getMenu($menuId)
    {
        $cacheKey = md5('myMenu' . $menuId);

        if (($menu = $this->cache->fetch($cacheKey)) === false) {
            $menu = $this->menu_model->build($menuId);

            $this->cache->store($cacheKey, $menu);
        }

        return $menu;
    }

    public function _render($templateName, $itemType = false, $itemId = false)
    {
        $menu = $this->_getMenu();

        if ($itemType && $itemId) {
            $id = $this->menu_model->getFirstLevelParentId($itemType, $itemId);

            $menu = $menu[$id]['sub_menu'];
        }

        $this->template->show($templateName, false, ['menu' => $menu]);
    }
}