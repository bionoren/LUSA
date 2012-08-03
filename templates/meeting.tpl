{* @param Meeting $meeting *}
{* @param BOOL $nontrad True if this is a nontraditional class. *}

{if $nontrad}
    <td headers="campusHeader">
        {$meeting->campusName}
    </td>
{/if}
<td headers="profHeader">
    <!--<a href="'.$_SERVER["SCRIPT_NAME"].'#role=professor&amp;prof='.$this->prof.'&amp;submit=Submit">-->{$meeting->prof}<!--</a>-->
</td>
{if $nontrad}
    <td headers="dateHeader">
        {$meeting->startDayString} - {$meeting->endDayString}
    </td>
{/if}
<td headers="dayHeader">
    {$meeting->dayString}
</td>
<td headers="timeHeader">
    {$meeting->startTimeString}-{$meeting->endTimeString}
</td>