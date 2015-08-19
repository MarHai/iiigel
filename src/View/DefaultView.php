<?php namespace Iiigel\View;

abstract class DefaultView {
    public function __construct() {
        putenv('LANG='.$GLOBALS['sLanguage']);
        setlocale(LC_ALL, $GLOBALS['sLanguage']);
        bindtextdomain($GLOBALS['sDomain'], 'res/i18n/');
        bind_textdomain_codeset($GLOBALS['sDomain'], 'UTF-8');
        textdomain($GLOBALS['sDomain']);
    }
    
    /**
     * Render data with template and return completed HTML code or appropriate equivalent for AJAX mode.
     * 
     * @return string HTML or AJAX equivalent code
     */
    abstract public function render();
    
    public function makeCamelCaseNicer($_sCamelCase) {
        return implode(' ', preg_split(
            '/(?#! splitCamelCase Rev:20140412)
            # Split camelCase "words". Two global alternatives. Either g1of2:
              (?<=[a-z])      # Position is after a lowercase,
              (?=[A-Z])       # and before an uppercase letter.
            | (?<=[A-Z])      # Or g2of2; Position is after uppercase,
              (?=[A-Z][a-z])  # and before upper-then-lower case.
            /x',
            substr($_sCamelCase, 1)
        ));
    }
}

?>