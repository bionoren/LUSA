{* @param Main $main Instance of $main. *}

{extends file="page.tpl"}
{block name="body"}
    <div id="container">
        <form method="get" id="form" action="{$main}">
            <div id="header">
                <h1><a href="{$main}" style="text-decoration:inherit; color:inherit;">LUSA</a></h1>
                <ul id="options">
                    <li class="first">
                        <input type="radio" id="typeStudent" name="role" value="student"{if Main::isStudent()} checked="checked"{/if}/>
                        <label for="typeStudent">Student</label>
                        &nbsp;&nbsp;
                        <input type="radio" id="typeProf" name="role" value="prof"{if !Main::isStudent()} checked="checked"{/if}/>
                        <label for="typeProf">Professor</label>
                    </li>
                    <li>
                        <div style="display:inline">
                            <input type="radio" id="typeTraditional" name="type" value="trad"{if Main::isTraditional()} checked="checked"{/if}/>
                            <label for="typeTraditional">Traditional</label>
                            &nbsp;&nbsp;
                            <input type="radio" id="typeNonTraditional" name="type" value="non"{if !Main::isTraditional()} checked="checked"{/if}/>
                            <label for="typeNonTraditional">Non-Traditional</label>
                        </div>
                    </li>
                    {if !Main::isTraditional()}
                        <li>
                            <div style="display:inline">
                                <select name="campus" id="campusSelect">
<!--                                <option value="AUS"{if Main::getCampus() == "AUS"} selected='selected'{/if}>Austin</option>-->
                                    <option value="BED"{if Main::getCampus() == "BED"} selected='selected'{/if}>Bedford</option>
                                    <option value="DAL"{if Main::getCampus() == "DAL"} selected='selected'{/if}>Dallas</option>
                                    <option value="HOU"{if Main::getCampus() == "HOU"} selected='selected'{/if}>Houston</option>
                                    <option value="MAIN"{if Main::getCampus() == "MAIN"} selected='selected'{/if}>Longview</option>
                                    <option value="TYL"{if Main::getCampus() == "TYL"} selected='selected'{/if}>Tyler</option>
                                    <option value="WES"{if Main::getCampus() == "WES"} selected='selected'{/if}>Westchase</option>
                                    <option value="XOL"{if Main::getCampus() == "XOL"} selected='selected'{/if}>Online</option>
                                </select>
                                <label for="campusSelect" style="display:none">Select Campus</label>
                            </div>
                        </li>
                    {/if}
                    <li>
                        <div style="display:inline">
                            <select name="semester" id="semesterSelect">
                                {Main::printSemesterOptions()}
                            </select>
                            <label for="semesterSelect" style="display:none;">Select Semester</label>
                        </div>
                    </li>
                </ul>
            </div>
            <div id="body">
                {include strtolower(get_class($main))|cat:".tpl" student=$main}
            </div>
        </form>
        <div id="footer" class="print-no">
            <ul>
                <li class="print-no"><a href="javascript:void" onclick='window.open("http://www.letu.edu/academics/catalog/");'>Course Catalog</a></li>
                <li>Remember that LUSA <span style="color:red;">does not</span> register you for classes. You can <a href="javascript:void" onclick='window.open("https://my.letu.edu:91/cgi-bin/student/frame.cgi")'>log into MyLetu to register for classes</a>.</li>
                <li class="print-no">By using this, you agree not to sue (<a href="tos.php">blah blah blah</a>).</li>
            </ul>
        </div>
    </div>
{/block}