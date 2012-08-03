<?php

App::uses('Model', 'Model');

/**
 * Application model for Cake.
 *
 * This file is application-wide model file. You can put all
 * application-wide model-related methods here.
 *
 * PHP 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2011, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 0.2.9
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class AppCoreAppModel extends Model {
    /**
     * Contient le nom de la connexion précédemment utilisé, avant celle courante
     * 
     * @var string 
     */
    private $_oldDbConfig;
    /**
     * Bind le mode Containable en cas de besoin sur l'ensemble des modèles
     * 
     * @var array
     */
    var $actsAs = array('Containable');
    /**
     * Reduit par défaut, sauf spécification contraire, les jointures
     * 
     * @var int
     */
    var $recursive = -1;
    /**
     * Instance d'un objet CakeEmail
     * 
     * @var CakeEmail
     */
    var $cakeEmail = false;
    /**
     * Cet attribut peut etre utilisé lorsque l'on souhaite validé des champs
     * dynamiquement
     * 
     * @var type 
     */
    var $validateExtraFields = array();
    /**
     * Remplacement de la méthode paginateCount par un attribut surchargeable
     * 
     * @var mixed
     */
    var $paginateCount = false;
    /**
     * Cette fonction permet de gérer automatiquement les getters et les setters des objets en bases.
     * Nous faisons ici appel à une méthode dite "magique" de PHP.
     * En effet, à l'appel d'une méthode inconnue du model, PHP déclenchera automatiquement le processus de cette méthode.
     *
     * @param string $name Nom de la méthode appelée
     * @param string $arguments Nom des paramètres passé à cette méthode
     * @return Model objet
     */
    function __call($name,$arguments){
        $name_ = Inflector::underscore($name);
        if(in_array(substr($name_, 0, 4),array('set_','get_'))){// Si la méthode contient set_ ou get_
            $name_ = substr($name_, 4); // on supprime set_ ou get_ pour ne garder que l'attribut réel
            if($this->hasField($name_,true)){
                if(!empty($arguments)){// Il s'agit d'un setter
                    $this->data[$this->alias][$name_] = $arguments[0];
                }else{// Il s'agit d'un getter
                    if(!array_key_exists($this->alias,$this->data) || !array_key_exists($name_,$this->data[$this->alias]) || is_null($this->data[$this->alias][$name_])){
                        return false;
                    }
                    return $this->data[$this->alias][$name_];
                }
            }else{
                // Cas spécial de champ utilisé par les behavior
                if(in_array($name_,array('parent_node','upload_folder'))){
                    return parent::__call($name, $arguments);
                }
                if(!method_exists($this, $name_)){
                    trigger_error("Field '".$name_."' doesn't exist in object '".$this->alias."'");
                }
            }
        }else{
            return parent::__call($name, $arguments);
        }
    }
    /**
     * Cette méthode permet de modifier "on runtime" la connexion utilisé par 
     * le model courant
     * 
     * @param string $connection Le nom de la connexion à utiliser
     */
    function changeDataSource($connection)  {
        $this->_oldDbConfig = $this->useDbConfig;
        parent::setDataSource($connection);
        parent::__construct();
        // Surcharge du useTable
        $this->useTable = $this->table;
    }
    /**
     * Cette méthode permet de faire un reset de la connexion sur le model courant
     * Elle doit être utilisé notamment lorsque l'on souhaite faire un traitement
     * en boucle, mais que la connexion utilisée doit être modifié à chaque passage
     * 
     * @param string $ds La connexion qui doit être utilisé à la place de celle courante
     */
    function resetDataSource($ds = 'default'){
        $this->changeDataSource($ds);
        $this->dropDatasource($this->_oldDbConfig);
    }
    /**
     * Supprime virtuellement la connexion passé en paramètre
     * 
     * @param string $name Nom de la connexion
     * @return boolean
     */
    public function dropDatasource($name){
        return ConnectionManager::drop($name);
    }
        
    /**
     * Défini le champ id de l'objet courant
     * @param string $id 
     * @return void
     */
    function setId($id){
        $this->set('id',$id);
    }
    
    /**
     * Défini le status_nb de l'objet courant en tant que $status
     * 
     * @param string $status
     * @return void
     */
    function setStatusNb($status){
        $status = $this->switchStatusNbToNb($status);
        $this->set('status_nb',$status);
    }
    
    /**
     * Callback appelée avant la validation des données 
     * 
     * @return void
     */
    function beforeValidate(){
        // If the key 'status_nb' exists in the data and status_nb is found has a keyword in status
        if(is_array($this->data) && array_key_exists($this->alias, $this->data) && array_key_exists('status_nb', $this->data[$this->alias])  && array_key_exists($this->data[$this->alias]['status_nb'],$this->status_nb)){
            // Switch the keyword to the interger
            $this->data[$this->alias]['status_nb'] = $this->switchStatusNbToNb($this->data[$this->alias]['status_nb']);
        }
    }
    
    /**
     * Callback appelé juste avant la création d'une requete 'find'
     * Pour optimiser les requetes, on place contain à false et recursive à -1
     * 
     * @param array $queryData Un tableau de données correspondant à la requête
     * @return array La requête surchargée
     */
    public function beforeFind($queryData){
        if(!array_key_exists('contain',$queryData)){
            $queryData['contain'] = false;
        }
        if(!array_key_exists('recursive',$queryData)){
            $queryData['recursive'] = -1;
        }
        return $queryData;
    }
    
    /**
     * Vérifie si la valeur passée en paramètre est vide ou non
     * 
     * @param mixed $value
     * @return boolean 
     */
    function isEmpty($value){
        if(empty($value)){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * Cette fonction permet de se "détacher" de l'ensemble des modèles liés au modèle courant pour la prochaine requete find
     * Si vous souhaitez vous détachez de l'ensemble des modèles pour toutes les requetes find futures de l'action courante, mettez $reset à false
     *
     * Cette fonction est particulièrement utile dans le cas de l'utilisation des update
     * 
     * @param $reset Si vaut 'false', supprimera l'ensemble des relations pour toutes les futures requetes find de l'action courante
     * @return Model objet
     */
    function unbindModelAll($reset = true){
        $unbind = array();
        foreach ($this->belongsTo as $model=>$info){
            $unbind['belongsTo'][] = $model;
        }
        foreach ($this->hasOne as $model=>$info){
            $unbind['hasOne'][] = $model;
        }
        foreach ($this->hasMany as $model=>$info){
            $unbind['hasMany'][] = $model;
        }
        foreach ($this->hasAndBelongsToMany as $model=>$info){
            $unbind['hasAndBelongsToMany'][] = $model;
        }
        parent::unbindModel($unbind,$reset);
    }
    
    /**
     * Récupère la valeur du status_nb en fonction de son $nom
     * 
     * @param string $name
     * @return int
     */
    public function switchStatusNbToNb($name){
        return $this->status_nb[$name];
    }
    
    /**
     * Cette fonction permet de calculer le prix TTC à partir
     * d'un prix ht et d'une tva
     * 
     * @param string $price_ht Le prix HT à partir duquel on souhaite faire le calcul
     * @param boolean $vat_price_only Si vaut true, le prix retourné sera le montant de la tva
     * par rapport au prix initial du produit. Si false, on retournera le prix ttc
     * @param string $vat
     * @return string La valeur calculée correctement formattée 
     */
    public function calculateTTC($price_ht,$vat_price_only=false,$vat = null){
        // Si aucune tva n'est passée en paramètre, on prend celle de l'application
        if(!isset($vat))
            $vat = Configure::read('App.vat');
        $price = (($price_ht*$vat)/100);
        // Si vaut true, on retourne seulement le montant de la tva par rapport
        // au prix de départ ht
        if($vat_price_only == true)
            return number_format ($price, 2, '.', ',');
        // Sinon on retourne le prix TTC
        return number_format ($price+$price_ht, 2, '.', ',');
    }
    
    /**
     * Fonction qui permet d'encoder en json les données
     * passées en paramètre
     * 
     * @param array $data
     * @return json object 
     */
    public function encodeData($data){
        return json_encode($data);
    }
    
    /**
     * Fonction qui permet de décoder (sous forme de tableau),
     * les données passées en paramètre
     * 
     * @param array $data
     * @return json object 
     */
    public function decodeData($data){
        // le true permet de reconvertir les objets json en tableau
        return json_decode($data,true);
    }
    
    /**
     * Cette fonction permet de préparer les données, lorsque celle-ci
     * doivent être stocké dans un champ au format json.
     * 
     * Cette fonction prend en compte le fait que des données peuvent 
     * déjà exister dans l'objet et fait un merge entre les données 
     * déjà existante et les nouvelles
     * 
     * @param array $data
     * @return array 
     */
    public function prepareDataToEncode($data = array()){
        if(empty($data)){
            return array();
        }
        // Si l'objet courant existe déjà
        if($this->exists()){
            // On recupère les données en base
            $model = $this->alias;
            $method = "get{$model}DataById";
            $db_data = $this->$method();
            if(false !== $db_data){
                // On désérialise
                $db_data = $this->decodeData($db_data);
                // On remplace les anciennes données par les nouvelles
                $data = array_replace_recursive($db_data,$data);
            }
        }
        // on serialise
        $data = $this->encodeData($data);
        // on retourne les données encodés
        return $data;
    }
    
    /**
     * Surcharge de la fonction log. Cette fonction permet
     * simplement de créer un format de sauvegarde 
     * 
     * @param string $msg Texte à logguer
     * @param string $file Fichier à utiliser
     * @param Exception $e L'instance d'une Exception
     * @param string $type Type d'erreur
     * @return void
     */
    function log($msg,$file = null,$e = null,$type = null){
        // Si les paramètres surchargés sont présents
        if(isset($file) && isset($e) && isset($type)){
            (is_object($e))?$message=$e->getMessage():$message=$e;
            parent::log("###########ERREUR : $type############",$file);
            parent::log($msg,$file);
            parent::log("Détail de l'erreur: ".$message,$file);
            parent::log("############################",$file);
        }else{
            parent::log($msg,$file);
        }
    }
    
     /**
      * Fonction qui permet de "splitter" un tableau et de concaténer les valeurs
      * en une liste chacune étant encadré par le $sep caractère
      * 
      * @param array $array Tableau de valeurs
      * @param string $sep Séparateur de valeur
      * @return string
      */
    function splitArrayIntoList($array,$sep = "','"){
        $list = implode($sep, $array);
        return "'".$list."'";
    } 
}
