<?php
include_once 'header.php';
include_once 'methods.php';

echo '<div class="howto">';

echo '<h2>Login</h2>';

echo '
    <ul>
    <li>
    Use your NCBS or InStem login id to login. If it is your first time,
    you will be taken to a page to review your profile. Kindly 
    <tt>review/edit</tt> your details.
    If you are suppose to give Annual Work Seminar (AWS), you 
    <strong>must</strong> double check all entries. In case of discrepency, 
    write email to Academic Office.
    </li>
    <li>
    You top-right corner, there is a box where shortcut links are provided. Any 
    time you feel lost, click on <tt>MyHome</tt> link to go to your home.
    Whatever you can do with Hippo are listed on this page.
    </li>
    </ul>
    ' ;


echo "<h2>How do I book my thesis seminar? </h2>";
echo "
    See the section below. While booking, select the talk <tt>CLASS</tt> to 
    <tt>THESIS SEMINAR </tt>.
    ";

echo '<h2>How to book a public talk, lecture or seminar?</h2>';

echo "<ul>
    <li> Keep the photo and email id of speaker handy. You can continue without 
    them but they are very useful for preparing documents. We strongly
    recommend that you arrange photo and email id of speaker. Email of speaker 
    is never  publicly displayed.
    </li>
    <li>
    After login to <a href=\"https://ncbs.res.in/hippo\">Hippo</a> , go to 
   <tt>Register talk/seminar</tt> and fill details. First
   section is for speaker, second is for talk. Third (optional) contains 
   scheduling information. If there is already some event on your selected
   date/venue, booking will be ignored but talk will be registered. You can
   schedule it later by visiting <tt>Manage my talks</tt> link.
   </li>

    <li>
    If venue is available on given date and time, both talk and venue will be
    booked pending approval.  After approval, you can see your event <a href=\"https://ncbs.res.in/hippo/events.php\">Here</a>. It will also appear on calendar and emails will be sent
    to appropriate mailing lists at appropriate times.
    </li>
    </ul>";


echo '<h3> Editing/updating/scheduling talks</h3>';
echo "<ul>
    <li>
   Go to 'My Home' and click on 'Manage my talks'. You will see all upcoming
   talks registered by you. You can click on 'edit' button to edit the
   description and title.
    </li>
    <li>
    If it is not already scheduled, you can schedule it by clicking on 'Calendar' button.
    </li>
    
    </ul>";
    
echo '<h2>How to create a general booking request?</h2>';
echo "
    <ul>
        <li>
            Click <tt>QuickBook</tt> on the top-right corner to create your booking.
        </li>

        <li>
        You will be asked for date, start time, and end time.  And other optional
        information. click on <button disabled>Show me available venues</button>
        to see the available venues for given date/time.
        </li>

        <li>
        Press <button disabled>" . $symbCheck . "</button> in front of your 
            preferred venue, you will be
            asked for details of your booking. Please make sure you fill it under
            the right <tt>CLASS</tt> (e.g. <tt>THESIS SEMINAR, LAB MEETING, TALK</tt>
            etc. ).
        </li>

        <li>
        Once a request is made, your slot/venue is blocked and an email has been 
        sent your way. If you are importing work emails into other email accounts 
        such as google, please  check your spam folder also.
        </li>

        <li>
        Wait for someone from Hippo admins to confirm your request.
        You will receive confirmation/rejection email after approval/disapproval.
        </li>

        <li>
            <strong>Recurrent bookings</strong> can be created by filling the repeat pattern in your
            request, which would be for a maximum of 6 months period. You will receive an
            email alert to renew your booking, 5 to 7 days in advance before your last
            event expires.
        </li>
    </ul> 
    ";

echo '<h2>How to cancel or edit booking request/event?</h2>';

echo '
    <ul>
        <li>
        To edit or cancel request, click on <tt>My Home</tt> on the top right 
        corner and follow <tt>My booking requests</tt>.
        </li>
        <li>
        To edit or cancel officially confirmed events, click on <tt>My Home</tt> 
        on the top right corner and follow <tt>My booked events</tt>.
        </li>
    </ul>';

echo "<p>All the booked events can be viewed 
    <a target=\"_blank\" 
    href=\"https://www.ncbs.res.in/hippo/allevents.php\">here</a>
    </p>" ;

echo '<h1> Infrequently asked questions. </h1> ';

echo ' <h2> How is AWS schedule computed? </h2> ';
echo '
    The AWS schedule is computed by network-flow methods. For each available slot,
    we draw an edge from every speaker and put a cost on this edge. Lets say the 
    potential slot is x days away from the last AWS date. The cost is 
    minimum if x is 365 days; and it increases with (x-365). For x < 365, cost is 
    very hight.  Now the problem is to select edges such that this cost is minimized 
    i.e. all speakers give their AWS exactly 1 year after the joining or after their
    last AWS date. This is the general idea. The real situation more complicated that 
    this. Following policy is enforced.

    <ul>
    <li> All Ph.D/Int. PhD/Post.Doc are eligible for AWS. Everyone gets same weightage
    no matter where they are registered.
    </li>
    <li> Int.Phd. gets their fist AWS after 15 months, M.Sc. by research after 18 months (and only 1 ), and  everyone else gets it after 12 months.
    </li>
    <li> First 2 AWS are given most weightage i.e. they are most likely to come after
    an ideal gap of 12-13 months. Later AWS will be come at progressively slower
    rate (since we have more speakers than slots).
    </li>
    <li>
    No speaker is likely to get more than 5 AWS.
    </li>
    <li> The AWS admin can override any of the above and schedule AWS in any arbitrary
         manner.
    </li>
    </ul>

    Prodiving technical details of implementation is beyond the scope of this note. 
    However following image shows the cost function. The different curve represents
    the cost function of speaker with different number of given AWSs.

    <img src="compute_cost.py.png" style="width:500px;" >

    ';

echo '</div>';



echo "<p>TODO .. A lot here </p>";

?>

