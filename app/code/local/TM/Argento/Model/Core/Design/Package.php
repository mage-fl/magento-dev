<?php

class TM_Argento_Model_Core_Design_Package extends Mage_Core_Model_Design_Package
{
    /**
     * Added additional fallback rules:
     * package/[active_theme]_custom
     * package/[active_theme]
     * package/[default_theme]
     * enterprise/default,package/theme[configured with backend rules] - After default
     * base/default
     *
     * Check for files existence by specified scheme
     *
     * If fallback enabled, the first found file will be returned. Otherwise the base package / default theme file,
     *   regardless of found or not.
     * If disabled, the lookup won't be performed to spare filesystem calls.
     *
     * @param string $file
     * @param array &$params
     * @param array $fallbackScheme
     * @return string
     */
    protected function _fallback($file, array &$params, array $fallbackScheme = array(array()))
    {
        if ($this->_shouldFallback) {
            // tm modification #1
            $package = $this->getPackageName();
            if ('argento' !== $package) {
                return parent::_fallback($file, $params, $fallbackScheme);
            }

            $params['_theme'] .= '_custom';
            $filename = $this->validateFile($file, $params);
            if ($filename) {
                return $filename;
            }
            // tm modification #1

            foreach ($fallbackScheme as $try) {
                $params = array_merge($params, $try);
                $filename = $this->validateFile($file, $params);
                if ($filename) {
                    return $filename;
                }
            }

            // tm modification #2
            $themes = $this->getTheme('after_default');
            if ($themes && $themes !== $this->getTheme('default')) {
                foreach (explode(',', $themes) as $theme) {
                    $themeParts = explode('/', $theme);
                    if (count($themeParts) === 2) {
                        $params['_package'] = $themeParts[0];
                        $params['_theme']   = $themeParts[1];
                    } else {
                        $params['_package'] = $package;
                        $params['_theme']   = $themeParts[0];
                    }
                    $filename = $this->validateFile($file, $params);
                    if ($filename) {
                        return $filename;
                    }
                }
            }
            // tm modification #2

            $params['_package'] = self::BASE_PACKAGE;
            $params['_theme']   = self::DEFAULT_THEME;
        }
        return $this->_renderFilename($file, $params);
    }
}
