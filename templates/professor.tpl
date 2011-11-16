{* @param Professor $student *}

<div class="print-no">
    Select a professor:
    {$student->printProfDropdown($student->prof)}
</div>
<div id="schedule" style="text-align:center;">
    {include "profClassList.tpl"}
</div>