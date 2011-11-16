{* @param Student $student *}

<div class="print-no">
    <h2>Selected Classes</h2>
    <div id="classDropdowns"></div>
    <span id="schedHours">{$student->hours}</span> Credit Hours
</div>
<div id="schedule">
    <h2>Schedule</h2>
    <table class="full border">
        <thead>
            <tr>
                <th colspan="2" id="classHeader">Class</th>
                <th id="sectionHeader">Section</th>
                {if Main::isTraditional()}
                    <th id="profHeader">Prof</th>
                {else}
                    <th id="campusHeader">Campus</th>
                    <th id="profHeader">Prof</th>
                    <th id="dateHeader">Dates</th>
                {/if}
                <th id="dayHeader">Days</th>
                <th id="timeHeader">Time</th>
                <th id="registeredHeader">Registered/Size</th>
            </tr>
        </thead>
        <tbody id="classes">
            {include "classList.tpl" student=$student}
        </tbody>
    </table>
    <br/>
    <a href="javascript:window.print();">Print Schedule</a>
    <br/>
    <div style="text-align:center;">
        {$extra = $student->getPrintExtra()}
        <img id="scheduleImg" alt="Schedule" src="print.php?{Student::getPrintQS(Student::$common)}{$extra}" height="100%"/>
    </div>
</div>