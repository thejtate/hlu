<?php /* Smarty version Smarty-3.1.21-dev, created on 2016-11-28 14:29:10
         compiled from ".\templates\printLicenseBody.tpl" */ ?>
<?php /*%%SmartyHeaderCode:31231583c93966e2d12-09481263%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '2ea3558cd5561554c5f0af1f76c3e490d200d6e3' => 
    array (
      0 => '.\\templates\\printLicenseBody.tpl',
      1 => 1480351381,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '31231583c93966e2d12-09481263',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'employeeName' => 0,
    'certList' => 0,
    'key' => 0,
    'item' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.21-dev',
  'unifunc' => 'content_583c939679ba39_09310028',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_583c939679ba39_09310028')) {function content_583c939679ba39_09310028($_smarty_tpl) {?>      <div class="col-sm-4 badged">
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
        <?php  $_smarty_tpl->tpl_vars['item'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['item']->_loop = false;
 $_smarty_tpl->tpl_vars['key'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['certList']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['item']->key => $_smarty_tpl->tpl_vars['item']->value) {
$_smarty_tpl->tpl_vars['item']->_loop = true;
 $_smarty_tpl->tpl_vars['key']->value = $_smarty_tpl->tpl_vars['item']->key;
?>
            <?php $_smarty_tpl->tpl_vars['last'] = new Smarty_variable($_smarty_tpl->tpl_vars['key']->value, null, 0);?>
         <tr>
           <td style="max-width: 53px;"><?php echo $_smarty_tpl->tpl_vars['item']->value->shortname;?>
</td>
            <td style="min-width: 46px; white-space: nowrap"><?php echo $_smarty_tpl->tpl_vars['item']->value->firstcompleted;?>
</td>
           <td style="min-width: 44px; white-space: nowrap"><?php echo $_smarty_tpl->tpl_vars['item']->value->lastcompleted;?>
</td>
        </tr>
        <?php } ?>
    </tbody>
</table>
     </div>       
      </div>

       <?php }} ?>
