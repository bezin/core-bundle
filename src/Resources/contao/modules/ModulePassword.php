<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao;

use Patchwork\Utf8;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


/**
 * Front end module "lost password".
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */
class ModulePassword extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_password';


	/**
	 * Display a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			/** @var BackendTemplate|object $objTemplate */
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['lostPassword'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}

		return parent::generate();
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		/** @var PageModel $objPage */
		global $objPage;

		$GLOBALS['TL_LANGUAGE'] = $objPage->language;

		\System::loadLanguageFile('tl_member');
		$this->loadDataContainer('tl_member');

		// Set new password
		if (strlen(\Input::get('token')))
		{
			$this->setNewPassword();

			return;
		}

		// Username widget
		if (!$this->reg_skipName)
		{
			$arrFields['username'] = $GLOBALS['TL_DCA']['tl_member']['fields']['username'];
			$arrFields['username']['name'] = 'username';
		}

		// E-mail widget
		$arrFields['email'] = $GLOBALS['TL_DCA']['tl_member']['fields']['email'];
		$arrFields['email']['name'] = 'email';

		// Captcha widget
		if (!$this->disableCaptcha)
		{
			$arrFields['captcha'] = array
			(
				'name' => 'lost_password',
				'label' => $GLOBALS['TL_LANG']['MSC']['securityQuestion'],
				'inputType' => 'captcha',
				'eval' => array('mandatory'=>true)
			);
		}

		$row = 0;
		$strFields = '';
		$doNotSubmit = false;
		$strFormId = 'tl_lost_password_' . $this->id;

		// Initialize the widgets
		foreach ($arrFields as $arrField)
		{
			/** @var Widget $strClass */
			$strClass = $GLOBALS['TL_FFL'][$arrField['inputType']];

			// Continue if the class is not defined
			if (!class_exists($strClass))
			{
				continue;
			}

			$arrField['eval']['required'] = $arrField['eval']['mandatory'];

			/** @var Widget $objWidget */
			$objWidget = new $strClass($strClass::getAttributesFromDca($arrField, $arrField['name']));

			$objWidget->storeValues = true;
			$objWidget->rowClass = 'row_' . $row . (($row == 0) ? ' row_first' : '') . ((($row % 2) == 0) ? ' even' : ' odd');
			++$row;

			// Validate the widget
			if (\Input::post('FORM_SUBMIT') == $strFormId)
			{
				$objWidget->validate();

				if ($objWidget->hasErrors())
				{
					$doNotSubmit = true;
				}
			}

			$strFields .= $objWidget->parse();
		}

		$this->Template->fields = $strFields;
		$this->Template->hasError = $doNotSubmit;

		// Look for an account and send the password link
		if (\Input::post('FORM_SUBMIT') == $strFormId && !$doNotSubmit)
		{
			if ($this->reg_skipName)
			{
				$objMember = \MemberModel::findActiveByEmailAndUsername(\Input::post('email', true), null);
			}
			else
			{
				$objMember = \MemberModel::findActiveByEmailAndUsername(\Input::post('email', true), \Input::post('username'));
			}

			if ($objMember === null)
			{
				sleep(2); // Wait 2 seconds while brute forcing :)
				$this->Template->error = $GLOBALS['TL_LANG']['MSC']['accountNotFound'];
			}
			else
			{
				$this->sendPasswordLink($objMember);
			}
		}

		$this->Template->formId = $strFormId;
		$this->Template->username = \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['username']);
		$this->Template->email = \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['emailAddress']);
		$this->Template->action = \Environment::get('indexFreeRequest');
		$this->Template->slabel = \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['requestPassword']);
		$this->Template->rowLast = 'row_' . $row . ' row_last' . ((($row % 2) == 0) ? ' even' : ' odd');
	}


	/**
	 * Set the new password
	 */
	protected function setNewPassword()
	{
		$objMember = \MemberModel::findOneByActivation(\Input::get('token'));

		if ($objMember === null || $objMember->login == '')
		{
			$this->strTemplate = 'mod_message';

			/** @var FrontendTemplate|object $objTemplate */
			$objTemplate = new \FrontendTemplate($this->strTemplate);

			$this->Template = $objTemplate;
			$this->Template->type = 'error';
			$this->Template->message = $GLOBALS['TL_LANG']['MSC']['accountError'];

			return;
		}

		$strTable = $objMember->getTable();

		// Initialize the versioning (see #8301)
		$objVersions = new \Versions($strTable, $objMember->id);
		$objVersions->setUsername($objMember->username);
		$objVersions->setUserId(0);
		$objVersions->setEditUrl('contao/main.php?do=member&act=edit&id=%s&rt=1');
		$objVersions->initialize();

		// Define the form field
		$arrField = $GLOBALS['TL_DCA']['tl_member']['fields']['password'];

		/** @var Widget $strClass */
		$strClass = $GLOBALS['TL_FFL']['password'];

		// Fallback to default if the class is not defined
		if (!class_exists($strClass))
		{
			$strClass = 'FormPassword';
		}

		/** @var Widget $objWidget */
		$objWidget = new $strClass($strClass::getAttributesFromDca($arrField, 'password'));

		// Set row classes
		$objWidget->rowClass = 'row_0 row_first even';
		$objWidget->rowClassConfirm = 'row_1 odd';
		$this->Template->rowLast = 'row_2 row_last even';

		/** @var SessionInterface $objSession */
		$objSession = \System::getContainer()->get('session');

		// Validate the field
		if (strlen(\Input::post('FORM_SUBMIT')) && \Input::post('FORM_SUBMIT') == $objSession->get('setPasswordToken'))
		{
			$objWidget->validate();

			// Set the new password and redirect
			if (!$objWidget->hasErrors())
			{
				$objSession->set('setPasswordToken', '');

				$objMember->tstamp = time();
				$objMember->activation = '';
				$objMember->locked = 0; // see #8545
				$objMember->password = $objWidget->value;
				$objMember->save();

				// Create a new version
				if ($GLOBALS['TL_DCA'][$strTable]['config']['enableVersioning'])
				{
					$objVersions->create();
				}

				// HOOK: set new password callback
				if (isset($GLOBALS['TL_HOOKS']['setNewPassword']) && is_array($GLOBALS['TL_HOOKS']['setNewPassword']))
				{
					foreach ($GLOBALS['TL_HOOKS']['setNewPassword'] as $callback)
					{
						$this->import($callback[0]);
						$this->{$callback[0]}->{$callback[1]}($objMember, $objWidget->value, $this);
					}
				}

				// Redirect to the jumpTo page
				if (($objTarget = $this->objModel->getRelated('reg_jumpTo')) instanceof PageModel)
				{
					/** @var PageModel $objTarget */
					$this->redirect($objTarget->getFrontendUrl());
				}

				// Confirm
				$this->strTemplate = 'mod_message';

				/** @var FrontendTemplate|object $objTemplate */
				$objTemplate = new \FrontendTemplate($this->strTemplate);

				$this->Template = $objTemplate;
				$this->Template->type = 'confirm';
				$this->Template->message = $GLOBALS['TL_LANG']['MSC']['newPasswordSet'];

				return;
			}
		}

		$strToken = md5(uniqid(mt_rand(), true));
		$objSession->set('setPasswordToken', $strToken);

		$this->Template->formId = $strToken;
		$this->Template->fields = $objWidget->parse();
		$this->Template->action = \Environment::get('indexFreeRequest');
		$this->Template->slabel = \StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['setNewPassword']);
	}


	/**
	 * Create a new user and redirect
	 *
	 * @param MemberModel $objMember
	 */
	protected function sendPasswordLink($objMember)
	{
		$confirmationId = md5(uniqid(mt_rand(), true));

		// Store the confirmation ID
		$objMember = \MemberModel::findByPk($objMember->id);
		$objMember->activation = $confirmationId;
		$objMember->save();

		// Prepare the simple token data
		$arrData = $objMember->row();
		$arrData['domain'] = \Idna::decode(\Environment::get('host'));
		$arrData['link'] = \Idna::decode(\Environment::get('base')) . \Environment::get('request') . ((strpos(\Environment::get('request'), '?') !== false) ? '&' : '?') . 'token=' . $confirmationId;

		// Send e-mail
		$objEmail = new \Email();

		$objEmail->from = $GLOBALS['TL_ADMIN_EMAIL'];
		$objEmail->fromName = $GLOBALS['TL_ADMIN_NAME'];
		$objEmail->subject = sprintf($GLOBALS['TL_LANG']['MSC']['passwordSubject'], \Idna::decode(\Environment::get('host')));
		$objEmail->text = \StringUtil::parseSimpleTokens($this->reg_password, $arrData);
		$objEmail->sendTo($objMember->email);

		$this->log('A new password has been requested for user ID ' . $objMember->id . ' (' . \Idna::decodeEmail($objMember->email) . ')', __METHOD__, TL_ACCESS);

		// Check whether there is a jumpTo page
		if (($objJumpTo = $this->objModel->getRelated('jumpTo')) instanceof PageModel)
		{
			$this->jumpToOrReload($objJumpTo->row());
		}

		$this->reload();
	}
}
