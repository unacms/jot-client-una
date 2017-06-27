# Jot Messenger

```Jot Messenger``` module allows members to be in touch, communicate, instantly get to know about new messages, create private groups with friends, single talks, manually edit participants list and to add messenger block to different pages of the site.

**Messenger** requires ![Jot Server](https://github.com/unaio/jot-server) and if you don't have your own server we provide you with our own as default one for your site. **Jot Messenger** allows to send push notifications to members about new messages and to have this ability you should create account on OneSignal.com

How to install messenger:
1) You should go to ```UNA Studio -> Apps``` area, download **Messenger** module and install it.
2) If you like to enable **Push notification**, you should create account on http://onesignal.com/ and then setup ```Web Push``` notifications.

Below you may see short instructions about how to do this:
1. Go to App Settings and click Configure for the **Google Chrome, FireFox and Safari**.
<img src="https://j.gifs.com/wjDz2R.gif" width="480"  height="auto" />

2. Enter site URL
3. Enter icon URL **(RECOMMENDED size is 192x192)**. The file must be .png, .jpg, or .gif.
<img src="https://j.gifs.com/wjDzYJ.gif" width="480"  height="auto" />

More details about web push notifications setup you may find here for [**```http```**](https://documentation.onesignal.com/docs/web-push-sdk-setup-http) and for [**```https```**](https://documentation.onesignal.com/docs/web-push-sdk-setup-https)

4. Fill appropriate fields in Messenger settings with OneSignal options values.

If you setup **Jot Server**(https://github.com/unaio/jot-server) on your own server, you should fill 
```Server URL for messenger```
option with your own server's url and port number. (**Example: http://localhost:5443**)

Now **Jot Messenger** completely installed, enjoy :) 
