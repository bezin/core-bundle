<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Core
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao;


/**
 * Class ContentYouTube
 *
 * Content element "YouTube".
 * @copyright  Leo Feyer 2005-2014
 * @author     Leo Feyer <https://contao.org>
 * @package    Core
 */
class ContentYouTube extends ContentElement
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'ce_player';


	/**
	 * Show the YouTube link in the back end
	 * @return string
	 */
	public function generate()
	{
		if ($this->youtube == '')
		{
			return '';
		}

		if (TL_MODE == 'BE')
		{
			return '<p><a href="http://youtu.be/' . $this->youtube . '" target="_blank">http://youtu.be/' . $this->youtube . '</a></p>';
		}

		return parent::generate();
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$this->Template->size = '';

		// Set the size
		if ($this->playerSize != '')
		{
			$size = deserialize($this->playerSize);

			if (is_array($size))
			{
				$this->Template->size = ' width="' . $size[0] . '" height="' . $size[1] . '"';
			}
		}

		$this->Template->poster = false;

		// Optional poster
		if ($this->posterSRC != '')
		{
			if (($objFile = FilesModel::findByUuid($this->posterSRC)) !== null)
			{
				$this->Template->poster = $objFile->path;
			}
		}

		// Check for SSL (see #6900)
		$protocol = Environment::get('ssl') ? 'https://' : 'http://';

		$objFile = new \stdClass();
		$objFile->mime = 'video/x-youtube';
		$objFile->path = $protocol . 'www.youtube.com/watch?v=' . $this->youtube;

		$this->Template->isVideo = true;
		$this->Template->files = [$objFile];
		$this->Template->autoplay = $this->autoplay;
	}
}