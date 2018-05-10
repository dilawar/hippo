import dropbox

from credentials.server_paths import DROPBOX_ACCESS_TOKEN

dbx = dropbox.Dropbox(DROPBOX_ACCESS_TOKEN)

with open("main.py", "rb") as f:
    dbx.files_upload(f.read(), '/main2.py',
                     mode=dropbox.files.WriteMode.overwrite)
