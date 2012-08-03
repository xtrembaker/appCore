<?php

foreach(Configure::read('Routing.prefixes') as $prefix){
    Router::connect("/:lang/{$prefix}/:controller/:action/*", array('prefix' => $prefix, $prefix => true),array('lang' => formatLanguagesForRouting(),'persist' => array('lang')));
    Router::connect("/:lang/{$prefix}/:plugin/:controller", array('action' => 'index', 'prefix' => $prefix, $prefix => true),array('lang' => formatLanguagesForRouting(),'persist' => array('lang')));
    Router::connect("/:lang/{$prefix}/:plugin/:controller/:action/*", array('prefix' => $prefix, $prefix => true),array('lang' => formatLanguagesForRouting(),'persist' => array('lang')));
    Router::connect("/:lang/{$prefix}/:controller", array('action' => 'index', 'prefix' => $prefix, $prefix => true),array('lang' => formatLanguagesForRouting(),'persist' => array('lang')));
}

/**
 * Fonction qui permet de formatter correctement les langues pour le routing
 * 
 * @return string L'ensemble des langues concaténées pour le routing
 */
function formatLanguagesForRouting(){
    $languages = '';
    foreach(Configure::read('Config.languages') as $lang){
        $languages .= $lang."|";
    }
    return substr($languages, 0,-1);
}