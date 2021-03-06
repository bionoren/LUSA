{* @param Course $course Course object to display. *}
{* @param BOOL $optional True if this is an optional course. *}
{* @param Key to use for this course. *}

<tr id="{$course->getPrintQS()}" class="{$course->getBackgroundStyle()} {$key}"{if $optional} style="display:none;"{/if}>
    {if $optional}
        <td style="width:auto;" headers="classHeader">
{*            {if !$course->special}*}
                <input type="radio" name="{$key}" value="{$course->section}" onclick="Course.selected(this.name, this.parentNode.parentNode.id);"{if Student::isKept($course)} checked="checked"{/if}/>
                <label for="select{$course->uid}">{$course->number}-{$course->section}</label>
{*            {/if}*}
        </td>
    {else}
        <td headers="classHeader">{$course->courseID}-{$course->section}</td>
    {/if}
    <td headers="classHeader">{$course->getTitle()}</td>
<!--    <td headers="sectionHeader">{$course->section}</td>-->
    {include "meeting.tpl" meeting=$course->meetings[0] nontrad=!$course->trad}
<!--    <td headers="registeredHeader">{$course->currentRegistered}/{$course->maxRegisterable}</td> -->
</tr>
{for $i = 1; $i < count($course->meetings); $i++}
    <tr id="{$course->uid}{$i}" class="{$course->getBackgroundStyle()} {$course->courseID}"{if $optional} style="display:none;"{/if}>
        <td colspan="2">&nbsp;</td>
        {include "meeting.tpl" meeting=$course->meetings[$i] nontrad=!$course->trad}
    </tr>
{/for}