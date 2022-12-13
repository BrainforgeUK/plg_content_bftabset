<?php
/**
 * @package plugin Create tabs
 * @version 2.0.0
 * @copyright Copyright (C) 2018-2020 Jonathan Brain - brainforge. All rights reserved.
 * @license GPL
 * @author http://www.brainforge.co.uk
 */

// No direct access
use Joomla\CMS\Factory;

defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

class plgContentBftabset extends JPlugin
{
	const TABSETSTART = '{bftabset-start}';
	const TABSETTAB = '{bftabset-tab';
	const TABSETEND = '{bftabset-end}';

	const TABSETPREFIX = 'bftabset-';

	private static $inmodule = false;
	private static $tabsetid = 0;
	private static $tabid = 0;
	private static $tabNameList = null;

	public function onContentPrepare($context, &$article, &$params, $limitstart)
	{
		$app = JFactory::getApplication();
		if(!$app->isClient('site')) return true;

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

		if (empty($tabs)) {
			return;
		}

		$thisTabsetName = self::TABSETPREFIX . (self::$tabsetid++);
		if (empty($article->id))
		{
			if (self::$inmodule)
			{
				// Can only be used in one module on a page
				return;
			}
			$tabPrefix = self::TABSETPREFIX . '0-tab-';
			self::$inmodule = true;
		}
		else
		{
			$tabPrefix = self::TABSETPREFIX . $article->id . '-tab-';
		}

		$active = JFactory::getApplication()->input->getVar('tabid', null);
		if (!preg_match('/^' . $tabPrefix . '[0-9]+$/', $active))
		{
			$active = $tabPrefix . self::$tabid;
		}

		self::$tabNameList = array();
		$tabSet = JHtml::_('bootstrap.startTabSet', $thisTabsetName);
		foreach($tabs as $title=>$content)
		{
			$thisTabName = $tabPrefix . (self::$tabid++);
			self::$tabNameList[] = $thisTabName;
			$tabSet .= JHtml::_('bootstrap.addTab', $thisTabsetName, $thisTabName, $title);
			$tabSet .= $content;
			$tabSet .= JHtml::_('bootstrap.endTab');
		}
		$tabSet .= JHtml::_('bootstrap.endTabSet');

		$article->text = substr($article->text, 0, $tabsetStart) .
			$tabSet .
			substr($article->text, $tabsetEnd);

		JFactory::getDocument()->addScriptDeclaration('
jQuery( document ).ready(function() {
	var hash = location.hash;
	if (!hash) {
		hash = "#' . $active . '";
	}
	var $a = jQuery(".nav-tabs a[href=\"" + hash + "\"]");
	if ($a.length) $a.tab("show");
});
');

		$doc = JFactory::getDocument();
		if($this->params->get('cssmode'))
		{
			$css = trim($this->params->get('customcss'));
			if (!empty($css))
			{
				$doc->addStyleDeclaration($css);
			}
		}
		if($this->params->get('jsmode'))
		{
			$js = trim($this->params->get('customjs'));
			if (!empty($js))
			{
				$doc->addScriptDeclaration($js);
			}
		}

		return;
	}

	/**
	 * Listener for the `onAfterRender` event
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function onAfterRender()
	{
		$app = JFactory::getApplication();
		if(!$app->isClient('site')) return true;

		$documentbody = Factory::getApplication()->getBody();

		$documentbody = preg_replace_callback(
			'@/(' . self::TABSETPREFIX . '[0-9]+-tab-[0-9]+)[^"]*"@',
			function ($matches) {
				if (!empty(self::$tabNameList) && in_array($matches[1], self::$tabNameList))
				{
					return '#' . $matches[1] . '" onclick=\'
var $a = jQuery(".nav-tabs a[href=\"#' . $matches[1] . '\"]");
if ($a.length) {
  $a.tab("show");
  $a.scrollTo();
}
return false;					
\'';
				}
				return '#' . $matches[1] . '"';
			},
			$documentbody
		);

		Factory::getApplication()->setBody($documentbody);
	}
}
?>
