"""global_data.py: 

Keep global data here.

"""
    
__author__           = "Dilawar Singh"
__copyright__        = "Copyright 2017-, Dilawar Singh"
__version__          = "1.0.0"
__maintainer__       = "Dilawar Singh"
__email__            = "dilawars@ncbs.res.in"
__status__           = "Development"

import networkx as nx
from collections import defaultdict
import datetime

g_ = nx.DiGraph( )

# All AWS entries in the past.
aws_ = defaultdict( list )

# Upcoming AWS
upcoming_aws_ = { }
upcoming_aws_slots_ = defaultdict( list )

# AWS scheduling requests are kept in this dict.
aws_scheduling_requests_ = {}

# Upcoming AWS
upcoming_aws_ = { }
upcoming_aws_slots_ = defaultdict( list )

# AWS scheduling requests are kept in this dict.
aws_scheduling_requests_ = {}

# These speakers must give AWS.
speakers_ = { }

# List of holidays.
holidays_ = {}

# Specialization
specialization_ = { }
specialization_list_ = [ ]
speakersSpecialization_ = { }
specializationFreqs_ = { }

# freshers
freshers_ = set( )


fmt_ = '%Y-%m-%d'

