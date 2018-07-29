<?php
namespace Fab\Vidi\ViewHelpers\Tca;

/*
 * This file is part of the Fab/Vidi project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * View helper which wraps the TCA Table service.
 */
class VidiViewHelper extends AbstractViewHelper
{

    /**
     * Returns a vidi configuration value from the TCA Table service according to a key.
     *
     * @param string $key
     * @param string $dataType
     * @return string
     */
    public function render($key, $dataType)
    {
        $tca = $GLOBALS['TCA'][$dataType]['vidi'];

        return $tca[$key] ?? null;
    }

}
