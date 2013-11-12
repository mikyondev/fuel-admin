<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Jerem
 * Date: 08/11/13
 * Time: 00:03
 * To change this template use File | Settings | File Templates.
 */

class Uri extends Fuel\Core\Uri{

   public function __construct($uri = NULL)
    {
        parent::__construct($uri);
        $this->detect_language();
    }
 
    public function detect_language()
    {
        if ( !\Input::get('lang'))
        {
            return false;
        }
 
        $first = \Input::get('lang');
        $locales = Config::get('locales');
 
        if(array_key_exists($first, $locales))
        {
            logger(\Fuel::L_WARNING,'lang: '.$first.' locale: '.$locales[$first],__METHOD__);
            Config::set('language', $first);
            Config::set('locale', $locales[$first]);

            
        }
        else
        {
            \Messages::error('Language or Locale not supported');

        }
    }
}