<div class="crm-block crm-form-block crm-contact-custom-search-form-block">
<div class="crm-accordion-wrapper crm-custom_search_form-accordion {if $rows}collapsed{/if}">
    <div class="crm-accordion-header crm-master-accordion-header">
      {ts}Edit Search Criteria{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
        <table class="form-layout-compressed">
            {* Loop through all defined search criteria fields (defined in the buildForm() function). *}
            {foreach from=$elements item=element}
                <tr class="crm-contact-custom-search-form-row-{$element}">
                    <td class="label">{$form.$element.label}</td>
                    <td>{$form.$element.html}</td>
                </tr>
            {/foreach}
        </table>
        <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
</div><!-- /.crm-form-block -->

{if $rowsEmpty || $rows}
<div class="crm-content-block">
{if $rowsEmpty}
    {include file="CRM/Contact/Form/Search/Custom/EmptyResults.tpl"}
{/if}

{if $summary}
    {$summary.summary}: {$summary.total}
{/if}

{if $rows}
  <div class="crm-results-block">
    {* Search request has returned 1 or more matching rows. Display results and collapse the search criteria fieldset. *}
      {* This section displays the rows along and includes the paging controls *}
      <div class="crm-search-results">

        {include file="CRM/common/pager.tpl" location="top"}

        {* Include alpha pager if defined. *}
        {if $atoZ}
            {include file="CRM/common/pagerAToZ.tpl"}
        {/if}

        {strip}
        <table class="selector row-highlight" summary="{ts}Search results listings.{/ts}">
            <thead class="sticky">
                <tr>
                {foreach from=$columnHeaders item=header}
                    <th scope="col">
                        {if $header.sort}
                          {if $header.sort neq 'altered_by_contact_id'}
                            {assign var='key' value=$header.sort}
                            {$sort->_response.$key.link}
                          {/if}
                        {elseif $header.name}
                            {$header.name}
                        {/if}
                    </th>
                {/foreach}
                </tr>
            </thead>

            {counter start=0 skip=1 print=false}
            {foreach from=$rows item=row}
                <tr id='rowid{$row.id}' class="{cycle values="odd-row,even-row"}">
                    {foreach from=$columnHeaders item=header}
                        {assign var=fName value=$header.sort}
                        {if $fName eq 'sort_name'}
                            <td><a href="{crmURL p='civicrm/contact/view' q="reset=1&cid=`$row.altered_by_contact_id`&key=`$qfKey`&context=custom"}">{$row.sort_name}</a></td>
                        {elseif $fName neq 'altered_by_contact_id'}
                            <td>{$row.$fName}</td>
                        {/if}
                    {/foreach}
                </tr>
            {/foreach}
        </table>
        {/strip}

        {include file="CRM/common/pager.tpl" location="bottom"}

        </p>
    {* END Actions/Results section *}
    </div>
    </div>
{/if}



</div>
{/if}
