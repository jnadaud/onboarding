<?php
/*
* 2007-2014 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2014 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
if (!defined('_PS_VERSION_'))
	exit;

class OnBoarding extends Module
{
	public function __construct()
	{
		$this->name = 'onboarding';
        $this->bootstrap = true;
		$this->tab = 'administration';
		$this->version = '0.1.0';
		$this->author = 'PrestaShop';
		$this->displayName = $this->l('OnBoarding');
		$this->description = $this->l('The OnBoarding module greets first-time users to their PrestaShop back-office: through a small playful interface, it shows the user how to launch his/her shop in several easy steps.');

		parent::__construct();
	}

	public function install()
	{
		Configuration::updateValue('PS_ONBOARDING_CURRENT_STEP', 1);
		Configuration::updateValue('PS_ONBOARDING_STEP_1_COMPLETED', 0);
		Configuration::updateValue('PS_ONBOARDING_STEP_2_COMPLETED', 0);
		Configuration::updateValue('PS_ONBOARDING_STEP_3_COMPLETED', 0);
		Configuration::updateValue('PS_ONBOARDING_STEP_4_COMPLETED', 0);

		if (parent::install() && $this->registerHook('displayBackOfficeHeader')
			&& $this->registerHook('displayBackOfficeTop') && $this->installTab())
			return true;

		return false;
	}
	
	public function uninstall()
	{
		if (!parent::uninstall() || !$this->uninstallTab())
			return false;

		return true;
	}
	
	public function installTab()
	{
		$tab = new Tab();
		$tab->active = 1;
		$tab->class_name = "AdminOnboarding";
		$tab->name = array();

		foreach (Language::getLanguages(true) as $lang)
			$tab->name[$lang['id_lang']] = "Onboarding";

		$tab->id_parent = 99999;
		$tab->module = $this->name;
		return $tab->add();
	}
	
	public function uninstallTab()
	{
		$id_tab = (int)Tab::getIdFromClassName('AdminOnboarding');

		if ($id_tab)
		{
			$tab = new Tab($id_tab);
			return $tab->delete();
		}
		else
			return false;
	}

	public function hookDisplayBackOfficeHeader()
	{
		if (!$this->active)
			return;
		$controller = Tools::getValue('controller');
				
		if (Tools::isSubmit('onboarding'))
		{
			switch ($controller)
			{
				case 'AdminProducts':
					Configuration::updateValue('PS_ONBOARDING_STEP_1_COMPLETED', 1);
					break;
				case 'AdminPayment':
					Configuration::updateValue('PS_ONBOARDING_STEP_2_COMPLETED', 1);
					break;
				case 'AdminCarriers':
					Configuration::updateValue('PS_ONBOARDING_STEP_3_COMPLETED', 1);
					break;
				case 'AdminDashboard':
					Configuration::updateValue('PS_ONBOARDING_STEP_4_COMPLETED', 1);
					break;
			}
		}
		
		$this->context->controller->addCSS($this->_path.'css/onboarding.css');
		$this->context->controller->addJS($this->_path.'js/onboarding.js');
	}

	public function hookDisplayBackOfficeTop()
	{
		$steps = array();
		for ($i=1; $i<5; $i++)
		{
			$steps[$i] = Configuration::get('PS_ONBOARDING_STEP_'.$i.'_COMPLETED');

			/*
if ($steps[$i] == 1)
				Configuration::updateValue('PS_ONBOARDING_CURRENT_STEP', $i+1);
*/
		}
		
		$current_step = (int)Configuration::get('PS_ONBOARDING_CURRENT_STEP');
		$this->context->smarty->assign(array(
			'display_onboarding_modal' => (int)Tools::isSubmit('onboarding'),
			'next_step_link' => $this->getCurrentStepLink(Tools::isSubmit('onboarding') ? $current_step+1 : $current_step),
			'steps' => $steps,
			'current_step_banner' => Tools::isSubmit('onboarding') && $current_step < 4 ? $current_step+1 : $current_step,
			'current_step' => $current_step,
			'link' => $this->context->link,
			'employee' => $this->context->employee,
			'continue_editing_links' => array(
				'theme' => $this->context->link->getAdminLink('AdminThemes'),
				'product' => $this->context->link->getAdminLink('AdminProducts'),
				'import' => $this->context->link->getAdminLink('AdminImport'),
				'payment' => $this->context->link->getAdminLink('AdminPayment'),
				'carrier' => $this->context->link->getAdminLink('AdminCarriers'),
				)
		));

		return $this->display(__FILE__, 'backoffice_top.tpl');
	}
	
	public function getCurrentStepLink($id_step)
	{
		$links = array(
			1 => $this->context->link->getAdminLink('AdminThemes').'&onboarding',
			2 => $this->context->link->getAdminLink('AdminProducts').'&onboarding&addproduct',
			3 => $this->context->link->getAdminLink('AdminPayment').'&onboarding',
			4 => $this->context->link->getAdminLink('AdminCarriers').'&onboarding&onboarding_carrier',
		);

		return isset($links[$id_step]) ? $links[$id_step] : Context::getContext()->link->getAdminLink('AdminDashboard').'&onboarding';
	}
}
