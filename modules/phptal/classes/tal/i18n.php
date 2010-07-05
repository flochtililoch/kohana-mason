<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * I18n Class
 * PHPTAL_TranslationService GetText implementation.
 *
 * @package		PHPTAL
 * @author 		Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @author		Florent Bonomo
 */
class Tal_I18n extends Component_I18n implements PHPTAL_TranslationService
{

    /**
     * NOTICE : This method is needed by PHPTAL_TranslationService interface
     * The only thing it is supposed to do is setting _encoding variable.
	 *
	 * @access	public
     */
    public function setEncoding($enc){}

    /**
     * NOTICE : This method is needed by PHPTAL_TranslationService interface
     * The only thing it is supposed to do is calling setlocale().
     * Because its already done by Kohana, dont implements it
	 *
	 * @access	public
     */
    public function setLanguage(){}

}	// End Tal_I18n