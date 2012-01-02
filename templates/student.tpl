{* @param Student $student *}

<table style="border:0px; padding:0px; margin:0px;">
    <tr style="margin-left:0px; margin-right:0px;">
        <td valign="top" style="margin-right:0px; padding-right:0px;">
            <div class="print-no">
                <h2>Classes</h2>
                <div id="classDropdowns"></div>
                <span id="schedHours">{$student->hours}</span> Credit Hours
            </div>
            <br/>
            <div id="schedule">
                <table class="full" style="width:586px; border-spacing:0px;">
                    <thead>
                        <tr>
                            <th colspan="2" id="classHeader">Class</th>
            <!--                <th id="sectionHeader">Section</th>-->
                            {if Main::isTraditional()}
                                <th id="profHeader">Prof</th>
                            {else}
                                <th id="campusHeader">Campus</th>
                                <th id="profHeader">Prof</th>
                                <th id="dateHeader">Dates</th>
                            {/if}
                            <th id="dayHeader">Days</th>
                            <th id="timeHeader">Time</th>
            <!--                <th id="registeredHeader">Reg/Size</th>-->
                        </tr>
                    </thead>
                    <tbody id="classes">
                        {include "classList.tpl" student=$student}
                    </tbody>
                </table>
            </div>
        </td>
        <td style="margin-left:0px; padding-left:0px;">
            <a href="javascript:window.print();">Print Schedule</a>
            {$extra = $student->getPrintExtra()}
            <img id="scheduleImg" alt="Schedule" src="print.php?{Student::getPrintQS(Student::$common)}{$extra}" style="width:435px;"/>
        </td>
    </tr>
</table>