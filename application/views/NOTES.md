# AWS Manager

## Scheduling

This algorithm solves the following problem:

Given N AWS-slots with M (>N) speakers, schedule their AWS in such a way that:

0. Each slot gets 1 speaker.
1. No speaker gets to speak earlier than 11 months.
2. 1st and 2nd AWS of each speaker must not have gap of more than 13 months.
3. 3rd or more AWS may get scheduled in a way that gap is more than 13 months.
4. Depending M, a speaker may never get more than 4 AWS.

### Algorithm 

0. Get all user and date of their last AWS.
1. Get all possible date slots. Each Monday has 3 slots. TODO: Ignore holidays.
2. Construct a flow graph. Compute max-flow min-cost in graph.
3. Display results to AWS manager for approval.

### Application
