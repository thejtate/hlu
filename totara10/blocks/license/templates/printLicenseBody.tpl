      <div class="col-sm-4 badged">
          <div style="height: 320px; width: 100%;">
<div style="text-align:center; margin-top: 20px">
    <img src="images/Employee-License-Logo.png" />
    <h4 style="font-weight:bold">Motorized Equipment License</h4>
</div>
           
          
<div id="name-area"  style="text-align:center;border-bottom: 1px solid #c0c0c0;padding-bottom: 4px; padding-top: 6px">
    {$employeeName}
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
        {foreach from=$certList key=key item=item}
            {assign var=last value=$key}
         <tr>
           <td style="max-width: 53px;">{$item->shortname}</td>
            <td style="min-width: 46px; white-space: nowrap">{$item->firstcompleted}</td>
           <td style="min-width: 44px; white-space: nowrap">{$item->lastcompleted}</td>
        </tr>
        {/foreach}
    </tbody>
</table>
     </div>       
      </div>

       