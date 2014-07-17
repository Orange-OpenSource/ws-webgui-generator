ws-webgui-generator
===================
Generate a webgui from a webservice soap to have a interface easily and rapidly.
![dashboard](https://raw.githubusercontent.com/Orange-OpenSource/ws-webgui-generator/master/img/dashboard.png)

Why this project?
------------------
In my team we have soap services which change all the time and in same time we needed to have something better than SoapUi and a real interface for admin. So we decided to make a Webgui which will be auto-generated from a wsdl.
With this project you will have a good and strong interface for your webservice to use them.

How to install in 30seconds?
----------------
First you need a webserver with php, for example install [wampserver](http://www.wampserver.com/) and put this project inside `wamp/www` folder.

Go inside folder project and go to `conf/conf.yml` (you can't use tab in yml file, use space instead) and update it with your parameters. Replace the URI by default by your wsdl url.
Start wampserver and go to [http://localhost/ws-webgui-generator](http://localhost/ws-webgui-generator). Wait a little, your webgui is being generated.
Now your done, you can use your webgui, you have more things in this project to have something more cool :) looks downside.

How to create a better menu?
---------------------
Go inside project and `conf/board.yml` you will find a menu by default. Simply remove what you don't want (but don't remove menu variable) and add your tab. This webgui will search everything which contains tab you write from your soap method and put them inside.

How to feed your fields directly?
-----------------------
Once you've installed this project you will have a new folder called apiRemote, inside you will found two files:
- a `.php` file with your webservice's name
- a .yml file with your webservice's name
Take a look to yml file will find something like this:
```yml
dataCenter:
  type_form: input
  connector: ''
```
`dataCenter` here correspond to a field name from your webservice it can be other field
You can change `type_form` value by `"select"`, `"input"` or `"textarea"` it will change your field form inside your app for this method.
you can change also `connector` value by `text`, `array` or even a `soap method` from your webservice (for this last one prefer use a `select` option in `type_form`) and your fields will be feed by this value(s).
