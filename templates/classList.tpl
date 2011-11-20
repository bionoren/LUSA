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
            <tr>
                <td style="border-bottom-color:black;" colspan="{$span}">
                    These classes have some options:
                </td>
            </tr>
            {$courses = $student->courses}
            {foreach $courses as $sections}
                {if !$sections[0]->partOfSet}
                    {if count($sections) > 1}
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
            <tr>
                <td style="border-bottom-color:black;" colspan="{$span}">
                    This is what worked for the multi-class selections:
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
                            {foreach $sections as $section}
                                {if $section@last}
                                    {$section->getTitle()}
                                {else}
                                    {$section->getTitle()},
                                {/if}
                            {/foreach}
                            ({count($sections)})
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