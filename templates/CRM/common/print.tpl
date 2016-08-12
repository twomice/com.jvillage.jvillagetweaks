{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{* Print.tpl: wrapper for Print views. Provides complete HTML doc. Includes print media stylesheet.*}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">

<head>
  <title>{if $pageTitle}{$pageTitle|strip_tags}{else}{ts}Printer-Friendly View{/ts} | {ts}CiviCRM{/ts}{/if}</title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <style type="text/css">@import url({$config->resourceBase}css/print.css);</style>

  {* [ML] added for Jmanage, required for advanced search results *}
  <style type="text/css">@import url({$config->extensionsURL}/com.jvillage.jvillagetweaks/css/print.css);</style>

  {* [ML] hack for #958 printing batch transactions, otherwise JS error *}
  {literal}
  <script>
    var CRM = CRM || {};
    CRM.config = CRM.config || {};
  </script>
  {/literal}

  <script src="{$config->resourceBase}/bower_components/jquery/dist/jquery.min.js"></script>
  <script src="{$config->resourceBase}/bower_components/jquery-ui/jquery-ui.min.js"></script>
  <script src="{$config->resourceBase}/bower_components/lodash-compat/lodash.min.js"></script>
  <script src="{$config->resourceBase}/bower_components/select2/select2.min.js"></script>
  <script src="{$config->resourceBase}/bower_components/jquery-validation/dist/jquery.validate.min.js"></script>
  <script src="{$config->resourceBase}/packages/jquery/plugins/jquery.blockUI.min.js"></script>
  <script src="{$config->resourceBase}/bower_components/datatables/media/js/jquery.dataTables.min.js"></script>
  <script src="{$config->resourceBase}/packages/jquery/plugins/jquery.timeentry.min.js"></script>
  <script src="{$config->resourceBase}/js/Common.js"></script>
  <script src="{$config->resourceBase}/js/crm.ajax.js"></script>

  {* [ML] required for jquery-validate on some form (ex: Batch) *}
  <script src="/civicrm/ajax/l10n-js/en_US"></script>

  {* [ML] added for Jmanage, required to print accounting batch transactions (redmine:958) *}
  <script src="{$config->resourceBase}/packages/jquery/plugins/jquery.jeditable.min.js"></script>
  <script src="{$config->resourceBase}/packages/jquery/plugins/jquery.notify.min.js"></script>
  <script src="{$config->resourceBase}/js/jquery/jquery.crmeditable.js"></script>
  <script src="{$config->resourceBase}/js/crm.optionEdit.js"></script>
  <link type="text/css" rel="stylesheet" href="{$config->resourceBase}/bower_components/datatables/media/css/jquery.dataTables.min.css" media="all" />
  <link type="text/css" rel="stylesheet" href="{$config->resourceBase}/bower_components/font-awesome/css/font-awesome.min.css" media="all" />
  <link type="text/css" rel="stylesheet" href="{$config->resourceBase}/bower_components/jquery-ui/themes/smoothness/jquery-ui.min.css" media="all" />

  {crmRegion name='html-header' allowCmsOverride=0}{/crmRegion}
</head>

<body>
{if $config->debug}
  {include file="CRM/common/debug.tpl"}
{/if}
<div id="crm-container" class="crm-container" lang="{$config->lcMessages|truncate:2:"":true}" xml:lang="{$config->lcMessages|truncate:2:"":true}">
{crmRegion name='page-header' allowCmsOverride=0}{/crmRegion}
{* Check for Status message for the page (stored in session->getStatus). Status is cleared on retrieval. *}
{include file="CRM/common/status.tpl"}

{crmRegion name='page-body' allowCmsOverride=0}
<!-- .tpl file invoked: {$tplFile}. Call via form.tpl if we have a form in the page. -->
  {if $isForm and isset($formTpl)}
    {include file="CRM/Form/$formTpl.tpl"}
  {else}
    {include file=$tplFile}
  {/if}
{/crmRegion}


{crmRegion name='page-footer' allowCmsOverride=0}
  <script type="text/javascript">
    window.print();
  </script>
{/crmRegion}
</div> {* end crm-container div *}
</body>
</html>
