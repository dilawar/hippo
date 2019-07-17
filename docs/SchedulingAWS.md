= How is AWS schedule computed? 

The AWS schedule is computed by network-flow methods. For each available slot,
we draw an edge from every speaker and put a cost on this edge. Lets say the
potential slot is x days away from the last AWS date. The cost is minimum if x
is 365 days; and it increases with (x-365). For x < 365, cost is very hight.
Now the problem is to select edges such that this cost is minimized i.e. all
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
