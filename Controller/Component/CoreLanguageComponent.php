<?php
// Component defined in 'ContactManager' plugin
class CoreLanguageComponent extends Component {
    
    public $controller;
    
    private $__settings = array(
        'defaultLang' => '',
        'forceLang' => true
    );
    
    /**
     * 
     * 
     * @param ComponentCollection $collection
     * @param type $settings 
     */
    function __construct(ComponentCollection $collection, $settings = array()){
        parent::__construct($collection, $settings);
        $this->controller = $collection->getController();
        $this->__settings = $settings;
        if(empty($this->__settings['defaultLang'])){
            $error = 'You have to specified a default language';
            throw new InternalErrorException($error);
        }
        $redirect = isset($_REQUEST) && !empty($_REQUEST) && 
                    $this->__settings['forceLang'] && 
                    !isset($this->controller->request->params['lang']);
        // Redirect cause an error in console mode
        if($redirect){
            $this->controller->redirect(array(
                                            //'controller' => $this->controller->request->params['controller'],
                                            //'action' => $this->controller->request->params['action'],
                                            'lang' => $this->__settings['defaultLang']
                                            ));
        }
    }
    
    /**
     * This method is used to set (or to detect) the language what need 
     * to be used by the App.
     * 
     * @param type $lang
     * @throws InternalErrorException 
     */
    public function setLanguage($lang = null){
        $languages = Configure::read('Config.languages');
        if(!isset($lang)){
            if(isset($this->controller->request->params['lang'])){
                $lang = $this->controller->request->params['lang'];
            }
        }
        if(!in_array($lang,$languages)){
            $lang = $this->__settings['defaultLang'];
        }
        Configure::write('Config.language',$lang);
    }
}