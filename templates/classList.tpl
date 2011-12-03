{* @param Student $student *}

{$span = (Main::isTraditional())?7:9}
{if Main::haveSelections()}
    {if is_array($student->courses)}
        {$haveComplex = false}
        {if count(Student::$common) > 0}
            <tr>
                <td style="border-bottom-color:black;" colspan="{$span}">
                    These are the only times you can take these classes:
                </td>
            </tr>
        {/if}
        {foreach Student::$common as $section}
            {if !$section->partOfSet}
                {include "course.tpl" course=$section optional=false key=$section->courseID}
            {/if}
        {/foreach}

        {if count(Student::$common) < count($student->courses)}
            {$headersShown=false}
            {$courses = $student->courses}
            {foreach $courses as $sections}
                {if !$sections[0]->partOfSet}
                    {if count($sections) > 1}
                        {if !$headersShown}
                            {$headersShown=true}
                            <tr>
                                <td style="border-bottom-color:black;" colspan="{$span}">
                                    These classes have some options:
                                </td>
                            </tr>
                        {/if}
                        {$currentSection = current($sections)}
                        {$key = $currentSection->courseID}
                        <tr style="cursor:pointer;" class="{$key}" onclick="Course.toggle('{$key}');">
                            <td>
                                <span id="{$key}">+</span> {$key}
                            </td>
                            <td colspan="{$span-1}">
                                {$currentSection->getTitle()} ({count($sections)})
                            </td>
                        </tr>
                        {foreach $sections as $section}
                            {include "course.tpl" course=$section optional=true key=$key}
                        {/foreach}
                    {/if}
                {else}
                    {$haveComplex = true}
                {/if}
            {/foreach}
        {/if}
        {if $haveComplex}
            <tr style="height:20px; overflow:hidden; vertical-align:top;">
                <td style="border-bottom-color:black;" colspan="{$span}">
                    {literal}This is what worked for the multi-class selections: <a href="javascript:void" style="vertical-align:inherit;" onmouseover="$('helpDiv').setStyle({display: 'inline-block'})" onmouseout="$('helpDiv').setStyle({display: 'none'})">[?]</a>{/literal}
                    <div id="helpDiv" style="{*display:none;*}">When multiple classes are selected, LUSA will try to give you as many options as possible, and it won't fail so long as at least 1 section is workable out of all the selected classes.</div>
                </td>
            </tr>
            {$courses = $student->courses}
            {foreach $courses as $sections}
                {if $sections[0]->partOfSet}
                    {$currentSection = current($sections)}
                    {$key = $currentSection->courseID}
                    <tr style="cursor:pointer;" class="{$key}" onclick="Course.toggle('{$key}');">
                        <td colspan="{$span}">
                            <span id="{$key}">+</span>
                            {$lastTitle=""}
                            {$title = []}
                            {foreach $sections as $section}
                                {if $lastTitle != $section->getTitle()}
                                    {$lastTitle = $section->getTitle()}
                                    {append var="title" value=$lastTitle}
                                {/if}
                            {/foreach}
                            {implode(", ", $title)} ({count($sections)})
                        </td>
                    </tr>
                    {foreach $sections as $section}
                        {include "course.tpl" course=$section optional=true key=$key}
                    {/foreach}
                {/if}
            {/foreach}
        {/if}
    {else}
        <tr>
            <td id="error" style="color:red;" colspan="{$span}">
                Conflicts were found :(
                <br/>
                {$student->courses}
            </td>
        </tr>
    {/if}
{else}
    <tr>
        <td colspan="{$span}">
            No selections yet...
        </td>
    </tr>
{/if}