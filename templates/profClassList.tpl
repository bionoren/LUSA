{* @param Professor $student *}

{if $student->prof}
    <h2>Schedule</h2>
    <img id="scheduleImg" alt="Schedule" src="print.php?{Student::getPrintQS($student->profClassList[$student->prof])}" height="600"/>
    <br/>
{/if}