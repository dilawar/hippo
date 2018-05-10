"""
This needs JSON file created from intranet files
"""

import json
import re

from models.photography import PhotoObject as Photo
from user_constants import *

data = {}

try:
    with open(output_file_name + ".json", "r") as f:
        data = json.load(f)
except FileNotFoundError:
    print("You need %s.json file created by intranet.py script to use this "
          "function" % output_file_name)


def make_defaulter_email(photos: list, theme: str, time: str):
    photo_string = ""
    for i in range(len(photos)):
        photo_string += "(%s) %s (%s)" % (
            i + 1, photos[i].title, base_url + photos[i].url) + "\n"

    last_message = "%s photos which were uploaded last" % (len(photos) - 3)
    if len(photos) - 3 == 1:
        last_message = "last uploaded photo"
    replace_values = {
        "$USER$": photos[0].author,
        "$COUNT$": str(len(photos)),
        "$THEME$": theme,
        "$LIST$": photo_string,
        "$TIME$": time,
        "$LAST_COUNT$": last_message
    }

    email = ""
    with open("models/defaulters.txt") as f:
        for line in f:
            for key in replace_values:
                line = line.replace(key, replace_values.get(key))
            email += line

    filename = re.sub('[^A-Za-z0-9]+', '', photos[0].author) + ".txt"
    with open(filename, "w") as o:
        print(email, file=o)


def get_defaulters(theme: str, time: str):
    all_photos = []
    for p in data.values():
        all_photos.append(Photo.make(p))

    all_photographers = {x.author: 0 for x in all_photos}
    for p2 in all_photos:
        all_photographers[p2.author] += 1

    for author in all_photographers:
        if all_photographers.get(author) > 3:
            suspected_photos = []
            for p3 in all_photos:
                if p3.author == author:
                    suspected_photos.append(p3)
            make_defaulter_email(suspected_photos, theme, time)
