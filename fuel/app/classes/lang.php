<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Jerem
 * Date: 08/11/13
 * Time: 00:03
 * To change this template use File | Settings | File Templates.
 */

class Lang extends Fuel\Core\Lang{

    /**
     * Returns a (dot notated) language string
     *
     * @param   string       $line      key for the line
     * @param   array        $params    array of params to str_replace
     * @param   mixed        $default   default value to return
     * @param   string|null  $language  name of the language to get, null for the configurated language
     * @return  mixed                   either the line or default when not found
     */
    public static function get($line, array $params = array(), $default = null, $language = null)
    {
        ($default === null) and $default = str_ireplace(array('.','-','_'),array(': ',' ',' '),$line);

        return parent::get($line, $params, $default, $language);

    }

    /**
     * Save a language array to disk.
     *
     * @param   string          $file      desired file name
     * @param   string|array    $lang      master language array key or language array
     * @param   string|null     $language  name of the language to load, null for the configurated language
     * @return  bool                       false when language is empty or invalid else \File::update result
     * @throws  Fuel\Core\LangException
     */
    public static function save($file, $lang, $language = null)
    {
        if(!is_array( $lang))
        {
            try
            {
                !$language and $language = parent::get_lang();
                $temp = parent::$lines[$language];
                $lang = array($lang => $temp[$lang]);
            }
            catch(Exception $ex)
            {
                throw new \Fuel\Core\LangException('Language array not found!');
            }

        }
        parent::save($file, $lang, $language);
    }

}