# Login

Use your NCBS or InStem login id. If it is your first time, you will be taken to
a page to review your profile. Kindly review/edit your details. If you are
suppose to give Annual Work Seminar (AWS), you must double check all entries. In
case of discrepency, write email to Academic Office.

You top-right corner, there is a box where shortcut links are provided. Any time
you feel lost, click on MyHome link to go to your home. Whatever you can do with
Hippo are listed on this page.

# How do I book my thesis seminar? 

See the section below. While booking, select the talk `CLASS` to `THESIS
SEMINAR`.

# How to book a public talk, lecture or seminar?

_Keep the photo and email id of speaker handy. You can continue without them but
they are very useful for preparing documents. We strongly recommend that you
arrange photo and email id of speaker. Email of speaker is never publicly
displayed._

After login, go to `Book Talk/Seminar`.  First section is for speaker, second is
for talk. Third (optional) contains scheduling information. You should fill all
of these sections. 

If there is already some event on your requested date/venue, booking will be
ignored but talk will be registered. You can schedule it later by visiting
`Manage My Talks` link. After approval, you can see your event
[here](https://ncbs.res.in/hippo/events.php). Emails will be sent to appropriate
mailing lists at appropriate times.

# Editing/updating/scheduling talks

Go to `My Home` and click on `Manage My Talks`. You will see all upcoming talks
registered by you. You can click on 'Edit' button to edit the description and
title.  If it is not already scheduled, you can schedule it by clicking on
appropriate button.

# How to create a general booking request?

Click `QuickBook` on the top-right corner to create your booking.  You will be
asked for date, start time, and end time. And other optional information. Press
the button to see the available venues.

Press in front of your preferred venue, you will be asked for details of your
booking. Please make sure you fill it under the right CLASS (e.g. LAB MEETING,
TALK, CLASS etc.). __If you want to book any event for which emails is to be
sent to academic community, DO NOT use this interface__  Once a request is made,
your slot/venue is blocked; no one else can book on this venue of slot.  Wait
for someone from Hippo admins to confirm your request. You will receive
confirmation/rejection email after approval/disapproval.  Recurrent bookings can
be created by filling the repeat pattern in your request, which would be for a
maximum of 6 months period.  You will receive an email alert to renew your
booking twice; 7 and 2 days in advance before your last event.

_If you are importing work emails into other email accounts such as google, please check your spam folder_.

# How to cancel or edit booking request/event?

To edit or cancel request, click on `My Home` on the top right corner and follow
`My Booking Requests` link.  To edit or cancel confirmed requests, `My Booked
Events`.


# How is AWS schedule computed?

The AWS schedule is computed by network-flow methods. For each available slot,
we draw an edge from every speaker and put a cost on this edge. Lets say the
potential slot is x days away from the last AWS date. The cost is minimum if x
is 365 days; and it increases with (x-365). For x < 365, cost is very hight. Now
the problem is to select edges such that this cost is minimized i.e. all
speakers give their AWS exactly 1 year after the joining or after their last AWS
date. This is the general idea. The real situation more complicated that this.
Following policy is enforced.

All Ph.D/Int. PhD/Post.Doc are eligible for AWS. Everyone gets same weightage no
matter where they are registered.  Int.Phd. gets their fist AWS after 15 months,
M.Sc. by research after 18 months (and only 1 ), and everyone else gets it after
12 months.  First 2 AWS are given most weightage i.e. they are most likely to
come after an ideal gap of 12-13 months. Later AWS will be come at progressively
slower rate (since we have more speakers than slots).  No speaker is likely to
get more than 5 AWS.  The AWS admin can override any of the above and schedule
AWS in any arbitrary manner.

Prodiving technical details of implementation is beyond the scope of this note.
However following image shows the cost function. The different curve represents
the cost function of speaker with different number of given AWSs.

