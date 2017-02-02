<?php
/**
 * Created by PhpStorm.
 * User: CrazyTSTer
 * Date: 11.01.15
 * Time: 22:34
 */

include_once "Utils.php";
//include_once "RequestController.php";
define('MYSQL_GET_MENU_TREE', 'SELECT * FROM menu_tree ORDER BY item_sort_order');

define(
    'item',
    '<div class="item item--level#deep_level#">'
        . '<div class="icon icon--#img_class# icon--level#deep_level#"></div>'
        . '<div class="caption"><div class="caption_">#caption#</div></div>'
        . '<a href="#" class="b-link" onclick="showPage(\'#page#\'); return false;"></a>'
    . '</div>'
);

define(
'expandable_item',
    '<div class="js_item_container">'
        . item
        . '<div class="sub_menu">'
            . '<div class="sub_menu-corner"></div>'
            . '<div class="sub_menu-border"></div>'
            . '#sub_items#'
        . '</div>'
    . '</div>'
);

class Menu
{
    public static function run()
    {
        $db_data = '';

        if (DB::getInstance()->isDBReady()) {
            $db_data = DB::getInstance()->fetchMultipleRows(MYSQL_GET_MENU_TREE);
        }

        $menu_tree = self::createMenuTreeFromDBData($db_data);
        if (!$menu_tree) {
            Utils::reportError(__CLASS__, 'Could not generate menu, empty array has been returned', true);
        }
        $ret = self::generateHTML($menu_tree);
        return $ret;
    }

    private static function createMenuTreeFromDBData($data)
    {
        $sub_items = array();
        $menu_tree = array();

        foreach ($data as $key => $value) {
            if ($key === 'rows_count') {
                continue;
            }
            if (!is_array($value)) {
                break;
            }
            if (array_key_exists($value['item_id'], $sub_items)) {
                $sub_items[$value['item_id']] = array_merge($sub_items[$value['item_id']], $value);
            } else {
                $sub_items[$value['item_id']] = $value;
            }

            if ($sub_items[$value['item_id']]['item_parent_id'] == 0) {
                $menu_tree[] = &$sub_items[$value['item_id']];
            } else {
                $sub_items[$value['item_parent_id']]['sub_items'][] = &$sub_items[$value['item_id']];
            }
        }

        return $menu_tree;
    }

    private static function generateHTML($menu_tree, $sub_menu = false, $deepLevel = 0)
    {
        $html = '';
        foreach ($menu_tree as $item) {
            if (array_key_exists('sub_items', $item)) {
                $html .= Utils::addDataToTemplate(
                    expandable_item,
                    array(
                        'deep_level' => $deepLevel,
                        'img_class'  => $item['icon_class'],
                        'caption'    => $item['item_caption'],
                        'page'       => $item['action'],
                        'sub_items'  => self::generateHTML($item['sub_items'], true, $deepLevel + 1),
                    ),
                    false,
                    true
                );
            } else {
                $html .= Utils::addDataToTemplate(
                    item,
                    array(
                        'deep_level' => $deepLevel,
                        'img_class'  => $item['icon_class'],
                        'caption'    => $item['item_caption'],
                        'page'       => $item['action'],
                    ),
                    false,
                    true
                );
            }
        }

        return $html;
    }
}

