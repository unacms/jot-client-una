<img src="https://user-images.githubusercontent.com/22210428/27619378-391220cc-5c05-11e7-86ee-21edd7404298.png" width="300"> 

# Jot Messenger App for UNA

[![Jot Messenger for UNA All Releases](https://img.shields.io/github/downloads/unaio/jot-client-una/total.svg)](https://github.com/unaio/jot-client-una/archive/master.zip)

```Jot Messenger``` app for [UNA Communnity Mangement System](https://una.io) is a real-time private messenger as well as a public communication platform. Jot allows members to create private talks, group talks and participate in embedded conversaitons integrated with other UNA apps.

**Jot Messenger** requires the **[Jot Server](https://github.com/unaio/jot-server)**. **Jot Messenger** supports push notifications via OneSignal platform. 

How to install messenger to your UNA-powered site:
1) You should go to ```UNA Studio -> Apps```, download **Messenger** app, install and activate it.
2) If you like to enable **Push notification**, you should create account on http://onesignal.com/ and then setup ```Web Push``` notifications.

How to setup push notifications:
1. Go to App Settings and click Configure for the **Google Chrome, FireFox and Safari**.
<img src="https://j.gifs.com/wjDz2R.gif" width="480"  height="auto" />

2. Enter site URL
3. Enter icon URL **(RECOMMENDED size is 192x192)**. The file must be .png, .jpg, or .gif.
<img src="https://j.gifs.com/wjDzYJ.gif" width="480"  height="auto" />

More details about web push notifications setup you may find here for [**```http```**](https://documentation.onesignal.com/docs/web-push-sdk-setup-http) and for [**```https```**](https://documentation.onesignal.com/docs/web-push-sdk-setup-https)

4. Fill appropriate fields in Messenger settings with OneSignal options values.

If you setup **[Jot Server](https://github.com/unaio/jot-server)** on your own server, you should fill 
```Server URL for messenger```
option with your own server's url and port number. (**Example: http://localhost:5443**)

Now the **Jot Messenger** is completely installed. Enjoy! :)
