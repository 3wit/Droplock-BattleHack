# Droplock - BattleHack LA 2015
Our solution we built for the hackathon builds upon Dropbox to lock down a user’s computer, we call it Droplock.

Basically, a user logs into a webapp or can use their [Pebble](https://getpebble.com/) to lock or unlock their computer. When the system is put into a “lock” state. The compromised system immediately responds by taking pictures and saving location info into a hidden Dropbox folder.

This information is then synced with our servers and viewable online where a user can access it.

We felt the thief should have the option to redeem themselves, so we included: a method for the thief to pay it forward(using [JustGiving](https://home.justgiving.com/)) to donate a set amount to charities, or paying back a set amount for the device(using [Braintree](https://www.braintreepayments.com/)).

If thief fails to do either, the system is set up to shutdown after 30 minutes. On reboot the process begins again, essentially pestering the user until they submit.

However, if they do decide to clear their conscience then we wipe all data on the machine and the computer is now theirs. An email is then sent through [SendGrid](https://sendgrid.com/) to the original user notifying them of the new user’s payment action.

##Dependencies
- [CakePHP](http://cakephp.org/)
- rharder's [imagesnap](https://github.com/rharder/imagesnap)
- [Dropbox](https://www.dropbox.com/developers)
- [JustGiving](http://pages.justgiving.com/developer)
- [Braintree](https://developers.braintreepayments.com/javascript+node)
- [SendGrid](https://sendgrid.com/developers)
- [Pebble](http://developer.getpebble.com)

##Updates
Hopefully within the following months we will be updating as we go along, please excuse any horrible or redudant code as we were very tired after the 24 hour hackathon.

##Contact Info
- Ethan Wessel [ejwessel@gmail.com](ejwessel@gmail.com)
- Israel Torres [itorres1490@gmail.com](itorres1490@gmail.com)
- Brandon Whitney [brandontwhitney@gmail.com](brandontwhitney@gmail.com)
