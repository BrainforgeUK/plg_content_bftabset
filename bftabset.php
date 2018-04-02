<?php
/**
 * @package plugin Create tabs
 * @version 2.0.0
 * @copyright Copyright (C) 2018 Jonathan Brain - brainforge. All rights reserved.
 * @license GPL
 * @author http://www.brainforge.co.uk
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

class plgContentBftabset extends JPlugin
{
	const TABSETSTART = '{bftabset-start}';
	const TABSETTAB = '{bftabset-tab';
	const TABSETEND = '{bftabset-end}';

	static $tabsetid = 0;
	static $tabid = 0;

	public function onContentPrepare($context, &$article, &$params, $limitstart)
	{
		$app = JFactory::getApplication();
		if($app->isAdmin()) return true;

		$tabsetStart = strpos($article->text, self::TABSETSTART);
		if ($tabsetStart === false) return;
		$tabsetEnd = strpos($article->text, self::TABSETEND, $tabsetStart);
		if ($tabsetEnd === false) return;

		$tabsetText = substr($article->text, $tabsetStart, $tabsetEnd - $tabsetStart);
		if (preg_match('@' . self::TABSETTAB . '[^}]*}</p>@', $tabsetText)) return;
		$tabsetEnd += strlen(self::TABSETEND);

		$tabs = array();
		$tabTitleEnd = false;
		$tabTitle = '';
		$posn1 = 0;
		while (($posn2 = strpos($tabsetText, self::TABSETTAB, $posn1)) !== false)
		{
			if ($tabTitleEnd !== false)
			{
				$tabs[$tabTitle] = trim(substr($tabsetText, $tabTitleEnd+1, $posn2-$tabTitleEnd-1));
			}
			$posn2 += strlen(self::TABSETTAB);
			if (($tabTitleEnd = strpos($tabsetText, '}', $posn2)) === false) return;
			$tabTitle = trim(substr($tabsetText, $posn2, $tabTitleEnd-$posn2));
			$posn1 = $posn2;
			if (empty($tabTitle)) return;
		}

		if ($tabTitleEnd !== false)
		{
			$tabs[$tabTitle] = trim(substr($tabsetText, $tabTitleEnd+1));
		}

		$active = 'bftabset-tab-' . self::$tabid;
		$tabsetActive = JFactory::getApplication()->input->getVar('tabsetactive');
		if (!empty($tabsetActive))
		{
			if (@sscanf($tabsetActive, '%d,%d', $tabsetid, $tabid) == 2)
			{
				if ($tabsetid == self::$tabid &&
					$tabid >= 0 && $tabid < count($tabs))
				{
					$active = 'bftabset-tab-' . (self::$tabid + $tabid);
				}
			}
		}

		$thisTabsetName = 'bftabset-' . (self::$tabsetid++);
		$tabSet = JHtml::_('bootstrap.startTabSet', $thisTabsetName, array('active' => $active));
		foreach($tabs as $title=>$content)
		{
			$tabSet .= JHtml::_('bootstrap.addTab', $thisTabsetName, 'bftabset-tab-' . (self::$tabid++), $title);
			$tabSet .= $content;
			$tabSet .= JHtml::_('bootstrap.endTab');
		}
		$tabSet .= JHtml::_('bootstrap.endTabSet');

		$article->text = substr($article->text, 0, $tabsetStart) .
			$tabSet .
			substr($article->text, $tabsetEnd);
		return;
	}
}
?>
