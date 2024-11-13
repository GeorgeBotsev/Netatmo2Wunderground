# Netatmo2Wunderground
Publish NetAtmo Weather Station data to Wunderground

# How to setup
Go to https://dev.netatmo.com/apps/createanapp and create an app.
Make sure to populate the redirect URL properly to your script full path - e.g. https://example.com/netatmo_callback.php
Give your app permissions to read weather station data, or give it all possible permissions.

Take client ID and client secret and record them.

Go to https://www.wunderground.com/member/devices and take your Wunderground station and secret and record them

Plug all of the data in the script - client id, client secret, redirect url, wunderground station and key.

Execute the script.

I added the ability to save the obtained values to a .json file and another PHP to display them in a neat interface with some filtering.
