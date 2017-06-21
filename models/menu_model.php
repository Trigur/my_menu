<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Menu_model extends MY_Controller{
    private $language;
    private $expCurrUrl;

    public function __construct()
    {
        parent::__construct();
    }

    public function setLanguage($language)
    {
        $this->language = $language;
    }

    public function explodeCurrentUrl()
    {
        $this->expCurrUrl = explode('/', trim_slashes($this->uri->uri_string()));
    }

    public function isActiveLink($url)
    {
        $exp = $this->expCurrUrl;
        $exp2 = explode('/', trim_slashes($url));

        $matches = 0;
        foreach ($exp2 as $k => $v) {
            if ($v == $exp[$k]) {
                $matches++;
            }
        }

        return ($matches == count($exp2)) ? true : false;
    }

    public function build($menuId, $parentId = 0)
    {
        $this->db->select('t1.*, t2.title');
        $this->db->from('menus_data t1');
        $this->db->join('menu_translate t2', 't2.item_id = t1.id AND t2.lang_id = ' . $this->language['id'], 'left');
        $this->db->where('t1.menu_id', $menuId);
        $this->db->where('t1.parent_id', $parentId);
        $this->db->where('t1.hidden', 0);
        $this->db->order_by('t1.position', 'asc');

        $queryResult = $this->db->get()->result_array();

        if (empty($queryResult)) {
            return false;
        }

        $result = [];

        $this->explodeCurrentUrl();
        foreach ($queryResult as $key => $item) {
            $addData = unserialize($item['add_data']);
            unset($item['add_data']);
            if (is_array($addData)) {
                $item = array_merge($item, $addData);
            }

            $this->setItemUrl($item);

            if ($this->isActiveLink($item['url'])) {
                $item['is_active'] = true;
            }

            $result[$item['id']] = $item;
            $subMenu = $this->build($menuId, $item['id']);

            if ($subMenu) {
                $result[$item['id']]['sub_menu'] = $subMenu;
            }
        }

        return $result;
    }

    private function setItemUrl(&$item)
    {
        switch ($item['item_type']) {
            case 'page':
                $page = get_page($item['id']);
                $url = $page['full_url'];
                break;
            case 'category ':
                $category = getCategory($item['id']);
                $url = $category['path_url'];
                break;
            case 'module':
                $url = $item['mod_name'];
                break;
            case 'url':
                $url = $item['url'];
                break;
        }

        $item['url'] = $url;
    }

    public function getFirstLevelParentId($itemType, $itemId)
    {
        $this->db->where('item_type', $itemType);
        $this->db->where('item_id', $itemId);
        $item = $this->db->get('menus_data')->row_array();

        if (!$item) {
            return false;
        }

        if ($item['parent_id'] == 0) {
            return $item['id'];
        } else {
            return $this->getFirstLevelParentId($item['item_type'], $item['item_id']);
        }
    }
}