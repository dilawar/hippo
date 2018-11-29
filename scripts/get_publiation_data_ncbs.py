#!/usr/bin/env python3
import requests
import os
import re
import lxml
from collections import defaultdict

def main():
    years = range(1995, 2018)
    data = defaultdict(list)
    if not os.path.exists( 'pub.pickle' ):
        for y in years:
            url = 'https://www.ncbs.res.in/content/publications-%s' % y
            a = requests.get(url)
            if a.status_code == 200:
                txt = a.text
                print( txt )
                break

if __name__ == '__main__':
    main()
