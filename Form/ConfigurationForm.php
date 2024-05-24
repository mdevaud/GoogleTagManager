<?php
/*************************************************************************************/
/*      This file is part of the GoogleTagManager package.                           */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace GoogleTagManager\Form;

use GoogleTagManager\GoogleTagManager;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

/**
 * Class Configuration
 * @package GoogleTagManager\Form
 * @author Tom Pradat <tpradat@openstudio.fr>
 */
class ConfigurationForm extends BaseForm
{
    protected function buildForm(): void
    {
        $form = $this->formBuilder;

        $value = GoogleTagManager::getConfigValue(GoogleTagManager::GOOGLE_TAG_MANAGER_GMT_ID_CONFIG_KEY);
        $form->add(
            "gtmId",
            TextType::class,
            array(
                'data'  => $value,
                'label' => Translator::getInstance()->trans("Google Tag Manager Id", [], GoogleTagManager::DOMAIN_NAME),
                'label_attr' => array(
                    'for' => "gtmId"
                ),
            )
        );
    }
}
