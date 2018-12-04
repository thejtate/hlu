<?php
/* Smarty version 3.1.30, created on 2018-08-17 14:32:19
  from "C:\wamp\www\totara-10.0\blocks\license\templates\printLicenseBody.tpl" */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.30',
  'unifunc' => 'content_5b7722c368efe3_77465581',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '935b1b3cff3a7975c4da3fa49d3dfecd4c54d944' => 
    array (
      0 => 'C:\\wamp\\www\\totara-10.0\\blocks\\license\\templates\\printLicenseBody.tpl',
      1 => 1534533532,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_5b7722c368efe3_77465581 (Smarty_Internal_Template $_smarty_tpl) {
?>
      <div class="col-sm-4 badged">
          <div style="height: 320px; width: 100%;">
<div style="text-align:center; margin-top: 20px">
    <img src="images/Employee-License-Logo.png" />
    <h4 style="font-weight:bold">Motorized Equipment License</h4>
</div>
           
          
<div id="name-area"  style="text-align:center;border-bottom: 1px solid #c0c0c0;padding-bottom: 4px; padding-top: 6px">
    <?php echo $_smarty_tpl->tpl_vars['employeeName']->value;?>

</div>
          
<table width="100%" style="font-size:85% !important">
    <thead>
        <tr>
            <th style="max-width: 53px;">Equipment</th>
            <th>Class Date</th>
            <th>Exp. Date</th>
        </tr>
    </thead>
    <tbody>
        <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['certList']->value, 'item', false, 'key');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['key']->value => $_smarty_tpl->tpl_vars['item']->value) {
?>
            <?php $_smarty_tpl->_assignInScope('last', $_smarty_tpl->tpl_vars['key']->value);
?>
         <tr>
           <td style="max-width: 53px;"><?php echo $_smarty_tpl->tpl_vars['item']->value->shortname;?>
</td>
            <td style="min-width: 46px; white-space: nowrap"><?php echo $_smarty_tpl->tpl_vars['item']->value->firstcompleted;?>
</td>
           <td style="min-width: 44px; white-space: nowrap"><?php echo $_smarty_tpl->tpl_vars['item']->value->lastcompleted;?>
</td>
        </tr>
        <?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl);
?>

    </tbody>
</table>
     </div>       
      </div>

       <?php }
}
