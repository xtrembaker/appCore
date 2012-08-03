<?php

App::uses('TreeBehavior', 'Model/Behavior');

/**
 * Classe which extend the Tree Behavior 
 */
class CoreExtendTreeBehavior extends TreeBehavior {

    /**
     * This method build a recursive tree from parent branches
     * Ex:
     * $tab = array(
            array(
                'label' => 'test',
                'children' => array(
                    array(
                        'label' => 'test',
                        array('children' => array(
                                array(
                                    'label' => 'test'
                                )
                            )
                        )
                    ),
                    array(
                        'label' => 'test2'
                    ),
                    array('label' => 'test3')

                )
            ),
            'label' => 'test',
            'children' => array(
                array(
                    'label' => 'test',
                    array('children' => array(
                            array(
                                'label' => 'test'
                            )
                        )
                    )
                ),
                array(
                    'label' => 'test2'
                ),
                array('label' => 'test3')

            )
        );
     * 
     * 
     * @param type $itemList
     * @param type $parentId
     * @return null 
     * @see http://stackoverflow.com/questions/7046364/php-building-recursive-array-from-list
     */
    function buildRecursiveTree(&$model, $itemList, $parentId) {
        // return an array of items with parent = $parentId
        $result = array();
        foreach ($itemList as $key => $item) {
            if ($item[$model->name]['parent_id'] == $parentId) {
                $newItem['label'] = $item[$model->name]['name'];
                $children = $this->buildRecursiveTree($model,$itemList, $item[$model->name]['id']);
                (isset($children))?$newItem['children'] = $children : null;
                $result[] = $newItem;
            }
        }
        if (count($result) > 0) return $result;
        return null;
    }
}